<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Auditoria;
use App\Support\PeriodoDeTrabajo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrxCalificacion extends Model
{
    use Auditable;

    protected $table = 'trx_calificacion';

    // registrado_por_user_id lo pone guardarTabla().
    protected $fillable = ['aca_inscripcion_id', 'aca_evaluacion_id', 'valor'];

    protected function casts(): array
    {
        return ['aca_inscripcion_id' => 'integer', 'aca_evaluacion_id' => 'integer', 'valor' => 'decimal:2'];
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(AcaInscripcion::class, 'aca_inscripcion_id');
    }

    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(AcaEvaluacion::class, 'aca_evaluacion_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }

    // Decimales permitidos al capturar y en la definitiva (config/siges.php; la columna admite hasta 2).
    public static function decimales(): int
    {
        return max(0, min(2, (int) config('siges.calificaciones.decimales', 1)));
    }

    // Calificaciones y pesos se comparan en centésimas (enteros) para no arrastrar errores de punto flotante.
    public static function centesimos(mixed $valor): int
    {
        return (int) round((float) $valor * 100);
    }

    public static function formatear(int $centesimos): string
    {
        return number_format($centesimos / 100, 2, '.', '');
    }

    /**
     * Guarda la tabla de captura de una materia. $valores = [inscripción => [evaluación => valor | null]].
     * Un campo vacío no se guarda y una calificación guardada nunca se borra.
     * $capturar: puede capturar las evaluaciones abiertas (el docente de la materia).
     * $corregir: puede modificar cualquiera, incluso cerradas (calificaciones.corregir). Si se modifica una cerrada,
     * o quien guarda no puede capturar, se exige $motivo.
     * Periodo de la materia: en el activo, como siempre; en uno PASADO solo corrige Control Escolar (siempre con motivo)
     * y el resultado cerrado se recalcula solo si es ORDINARIO sin recursamiento (los demás se avisan); en uno FUTURO, nada.
     * Devuelve ['guardadas' => n, 'sin_recalcular' => [nombres de alumnos cuyo resultado vigente no cambió]].
     */
    public static function guardarTabla(AcaGrupoAsignatura $materia, array $valores, User $usuario, bool $capturar, bool $corregir, ?string $motivo): array
    {
        $periodo = $materia->periodoEscolar();
        $pasado = PeriodoDeTrabajo::tipo($periodo) !== PeriodoDeTrabajo::ACTIVO;
        if ($pasado) {
            PeriodoDeTrabajo::exigir(PeriodoDeTrabajo::CORREGIR, $periodo);
            if (! $corregir) {
                throw ValidationException::withMessages(['accion' => "El periodo {$periodo->nombre_visible} ya pasó: solo Control Escolar corrige sus calificaciones, con motivo."]);
            }
            $capturar = false;
        }

        return DB::transaction(function () use ($materia, $valores, $usuario, $capturar, $corregir, $motivo, $pasado) {
            // Bloquea las evaluaciones: nadie las cierra mientras se guarda.
            $evaluaciones = $materia->evaluaciones()->with('tipo')->lockForUpdate()->get()->keyBy('id');
            // INSCRITO del grupo y recursantes activos (las demás filas son de solo lectura).
            $inscritas = collect($materia->inscripcionesCalificables());
            $existentes = static::whereIn('aca_evaluacion_id', $evaluaciones->keys())->whereIn('aca_inscripcion_id', $inscritas->keys())
                ->get()->keyBy(fn (self $calificacion) => $calificacion->aca_inscripcion_id.'-'.$calificacion->aca_evaluacion_id);

            $cambios = [];
            $errores = [];
            foreach ($valores as $inscripcion => $fila) {
                foreach ((array) $fila as $evaluacionId => $valor) {
                    if (blank($valor)) {
                        continue;
                    }
                    $campo = "calificaciones.{$inscripcion}.{$evaluacionId}";
                    $evaluacion = $evaluaciones->get($evaluacionId);
                    if (! $evaluacion || ! $inscritas->has($inscripcion)) {
                        $errores[$campo] = 'Hay una calificación de un alumno que ya no está inscrito o de una evaluación que no es de esta materia. Recarga la página.';

                        continue;
                    }
                    $actual = $existentes->get("{$inscripcion}-{$evaluacionId}");
                    $nuevo = static::centesimos($valor);
                    if ($actual && static::centesimos($actual->valor) === $nuevo) {
                        continue;
                    }
                    if ($evaluacion->cerrada ? ! $corregir : ! ($capturar || $corregir)) {
                        $errores[$campo] = "{$evaluacion->tipo->nombre} está cerrada: solo Control Escolar corrige sus calificaciones.";

                        continue;
                    }
                    $cambios[] = [(int) $inscripcion, $evaluacion, $actual, $nuevo];
                }
            }
            if ($errores) {
                throw ValidationException::withMessages($errores);
            }

            $esCorreccion = collect($cambios)->contains(fn (array $cambio) => $cambio[1]->cerrada || ! $capturar);
            if ($esCorreccion) {
                if (blank($motivo)) {
                    throw ValidationException::withMessages(['motivo' => 'Escribe el motivo de la corrección: queda en la bitácora.']);
                }
                Auditoria::motivo($motivo);
            }

            $afectadas = [];
            foreach ($cambios as [$inscripcion, $evaluacion, $actual, $nuevo]) {
                $calificacion = $actual ?? new static(['aca_inscripcion_id' => $inscripcion, 'aca_evaluacion_id' => $evaluacion->id]);
                $calificacion->forceFill(['valor' => static::formatear($nuevo), 'registrado_por_user_id' => $usuario->id])->save();
                $afectadas[$inscripcion] = $inscripcion;
            }
            // Si el alumno ya tenía su resultado, se recalcula con el mismo motivo (en un periodo pasado, solo el ORDINARIO).
            $intactos = TrxResultadoAsignatura::recalcular($materia, array_values($afectadas), $usuario, $pasado);
            $nombres = AcaInscripcion::with('alumno.persona')->whereKey($intactos)->get()
                ->map(fn (AcaInscripcion $inscripcion) => $inscripcion->alumno->persona->nombre_completo)->sort()->values()->all();

            return ['guardadas' => count($cambios), 'sin_recalcular' => $nombres];
        });
    }
}
