<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Auditoria;
use App\Support\PeriodoDeTrabajo;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrxAsistenciaDiaria extends Model
{
    use Auditable;

    // Las altas vienen del escaneo o del cierre (su origen ya está en trx_registro_acceso); se auditan los cambios.
    protected array $auditarAcciones = ['UPDATE', 'DELETE'];

    public const PRESENTE = 'PRESENTE';

    public const RETARDO = 'RETARDO';

    public const FALTA = 'FALTA';

    public const JUSTIFICADA = 'JUSTIFICADA';

    // estado => [etiqueta]. El color del badge lo define <x-badge-estado> (App\View\Components\BadgeEstado).
    public const ESTADOS = [
        self::PRESENTE => ['Presente'],
        self::RETARDO => ['Retardo'],
        self::FALTA => ['Falta'],
        self::JUSTIFICADA => ['Justificada'],
    ];

    protected $table = 'trx_asistencia_diaria';

    protected $fillable = ['aca_inscripcion_id', 'fecha', 'estado'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(AcaInscripcion::class, 'aca_inscripcion_id');
    }

    // Lunes a viernes, no inhábil y dentro del periodo activo.
    public static function esDiaHabil(CarbonInterface $dia): bool
    {
        $periodo = AcaPeriodoEscolar::activo();

        return ! $dia->isWeekend()
            && $periodo !== null
            && $dia->toDateString() >= $periodo->fecha_inicio->toDateString()
            && $dia->toDateString() <= $periodo->fecha_fin->toDateString()
            && AcaDiaInhabil::whereDate('fecha', $dia->toDateString())->doesntExist();
    }

    // Se compara al minuto: llegar exactamente en el límite todavía cuenta.
    public static function estadoParaHora(AcaTurno $turno, CarbonInterface $hora): string
    {
        $llegada = $hora->copy()->startOfMinute();
        $entrada = $hora->copy()->setTimeFromTimeString($turno->hora_entrada)->startOfMinute();

        return match (true) {
            $llegada->lte($entrada->copy()->addMinutes($turno->tolerancia_minutos)) => self::PRESENTE,
            $llegada->lte($entrada->copy()->addMinutes($turno->limite_retardo_min)) => self::RETARDO,
            default => self::FALTA,
        };
    }

    public static function registrarEntrada(AcaInscripcion $inscripcion, CarbonInterface $hora): self
    {
        $estado = static::estadoParaHora($inscripcion->grupo->turno, $hora);
        $fila = static::where('aca_inscripcion_id', $inscripcion->id)->whereDate('fecha', $hora->toDateString())->lockForUpdate()->first();
        if (! $fila) {
            return static::create(['aca_inscripcion_id' => $inscripcion->id, 'fecha' => $hora->toDateString(), 'estado' => $estado]);
        }
        // Solo se corrige una FALTA del cierre; una JUSTIFICADA (u otra) se respeta.
        if ($fila->estado === self::FALTA) {
            Auditoria::motivoPorDefecto('Entrada registrada después del cierre del día');
            $fila->update(['estado' => $estado]);
        }

        return $fila;
    }

    public function justificar(string $motivo): void
    {
        // Solo en el periodo activo: la asistencia de un periodo pasado se consulta.
        PeriodoDeTrabajo::exigir(PeriodoDeTrabajo::OPERAR, $this->inscripcion()->with('grupo.periodo')->first()?->grupo->periodo);
        DB::transaction(function () use ($motivo) {
            $fila = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            if (! in_array($fila->estado, [self::FALTA, self::RETARDO], true)) {
                throw ValidationException::withMessages(['accion' => 'Solo se justifica una falta o un retardo.']);
            }
            Auditoria::motivo($motivo);
            $fila->update(['estado' => self::JUSTIFICADA]);
            $this->refresh();
        });
    }

    /**
     * Cierra el día en dos pasadas idempotentes para cada inscripción INSCRITO del periodo activo sin registro:
     *   1. JUSTIFICADA si el alumno tiene una incidencia que justifica falta y cubre ese día;
     *   2. FALTA para el resto.
     * Devuelve cuántos registros creó; en un día no hábil no hace nada.
     */
    public static function cerrarDia(CarbonInterface $dia): int
    {
        if (! static::esDiaHabil($dia)) {
            return 0;
        }
        $fecha = $dia->toDateString();
        $ahora = now()->toDateTimeString();
        $pendientes = fn (string $estado) => AcaInscripcion::query()->inscritas()->delPeriodoActivo()
            ->whereDate('fecha_inscripcion', '<=', $fecha)
            ->whereNotExists(fn ($asistencias) => $asistencias->from('trx_asistencia_diaria')
                ->whereColumn('trx_asistencia_diaria.aca_inscripcion_id', 'aca_inscripcion.id')->where('trx_asistencia_diaria.fecha', $fecha))
            ->selectRaw('aca_inscripcion.id, ?::date, ?, ?::timestamp, ?::timestamp', [$fecha, $estado, $ahora, $ahora]);
        // Cobertura por alumno (no por inscripción): sigue valiendo si cambió de grupo. Cuenta aunque la incidencia esté cerrada.
        $cubiertas = fn ($inscripciones) => $inscripciones->whereExists(fn ($incidencias) => $incidencias->from('trx_incidencia')
            ->join('cat_tipo_incidencia', 'cat_tipo_incidencia.id', '=', 'trx_incidencia.cat_tipo_incidencia_id')
            ->where('cat_tipo_incidencia.justifica_falta', true)
            ->whereColumn('trx_incidencia.per_alumno_id', 'aca_inscripcion.per_alumno_id')
            ->where('trx_incidencia.fecha_inicio', '<=', $fecha)
            ->whereRaw('coalesce(trx_incidencia.fecha_fin, trx_incidencia.fecha_inicio) >= ?::date', [$fecha]));
        $columnas = ['aca_inscripcion_id', 'fecha', 'estado', 'created_at', 'updated_at'];

        // INSERT ... SELECT ... ON CONFLICT DO NOTHING: no pasa por eventos (las altas no se auditan) y el UNIQUE evita duplicados.
        $justificadas = DB::table('trx_asistencia_diaria')->insertOrIgnoreUsing($columnas, $cubiertas($pendientes(self::JUSTIFICADA)));
        $faltas = DB::table('trx_asistencia_diaria')->insertOrIgnoreUsing($columnas, $pendientes(self::FALTA));

        return $justificadas + $faltas;
    }

    /**
     * Pasa a JUSTIFICADA las FALTA y RETARDO del alumno en los días que cubre la incidencia.
     * Fila por fila, para que cada cambio quede en la bitácora con su motivo.
     */
    public static function justificarPorIncidencia(TrxIncidencia $incidencia): int
    {
        $filas = static::whereIn('estado', [self::FALTA, self::RETARDO])
            ->whereBetween('fecha', [$incidencia->fecha_inicio->toDateString(), ($incidencia->fecha_fin ?? $incidencia->fecha_inicio)->toDateString()])
            ->whereHas('inscripcion', fn ($inscripciones) => $inscripciones->where('per_alumno_id', $incidencia->per_alumno_id))
            ->lockForUpdate()->get();
        Auditoria::conMotivo("Justificada por la incidencia #{$incidencia->id} ({$incidencia->tipo->nombre})", function () use ($filas) {
            $filas->each(fn (self $fila) => $fila->update(['estado' => self::JUSTIFICADA]));
        });

        return $filas->count();
    }
}
