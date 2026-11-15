<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Support\PeriodoDeTrabajo;

// Evaluación programada de una materia de grupo: una por tipo; los pesos de la materia suman 100.
class AcaEvaluacion extends Model
{
    use Auditable;

    // Los pesos se manejan en centésimas: 100 % = 10000.
    public const PESO_TOTAL = 10000;

    protected $table = 'aca_evaluacion';

    // "cerrada" solo cambia con cerrar().
    protected $fillable = ['cat_tipo_evaluacion_id', 'peso_porcentaje'];

    protected function casts(): array
    {
        return ['cat_tipo_evaluacion_id' => 'integer', 'peso_porcentaje' => 'decimal:2', 'cerrada' => 'boolean'];
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(AcaGrupoAsignatura::class, 'aca_grupo_asignatura_id');
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(CatTipoEvaluacion::class, 'cat_tipo_evaluacion_id');
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(TrxCalificacion::class, 'aca_evaluacion_id');
    }

    public function tieneCalificaciones(): bool
    {
        return $this->exists && $this->calificaciones()->exists();
    }

    // Cerrada o con calificaciones: ya no cambia su peso ni se borra.
    public function estaBloqueada(): bool
    {
        return $this->cerrada || $this->tieneCalificaciones();
    }

    // Alumnos calificables (INSCRITO del grupo y recursantes activos) que aún no tienen calificación en esta evaluación.
    public function inscripcionesSinCalificacion(): Builder
    {
        return AcaInscripcion::whereIn('id', array_keys($this->materia->inscripcionesCalificables()))
            ->whereDoesntHave('calificaciones', fn (Builder $calificaciones) => $calificaciones->where('aca_evaluacion_id', $this->id));
    }

    /**
     * Programa las evaluaciones de una materia. $pesos = [tipo de evaluación => peso en % | null]; null o vacío = sin esa evaluación.
     * Crea, cambia o quita según haga falta; las bloqueadas (cerradas o con calificaciones) deben quedar igual.
     */
    public static function programar(AcaGrupoAsignatura $materia, array $pesos): void
    {
        // Solo en el periodo activo (un periodo futuro se prepara, uno pasado se consulta).
        PeriodoDeTrabajo::exigir(PeriodoDeTrabajo::OPERAR, $materia->periodoEscolar());
        DB::transaction(function () use ($materia, $pesos) {
            AcaGrupoAsignatura::whereKey($materia->getKey())->lockForUpdate()->firstOrFail();
            $tipos = CatTipoEvaluacion::ordenados()->get()->keyBy('id');
            $actuales = $materia->evaluaciones()->lockForUpdate()->get()->keyBy('cat_tipo_evaluacion_id');
            $nuevos = [];
            foreach ($pesos as $tipo => $peso) {
                if (! $tipos->has($tipo)) {
                    throw ValidationException::withMessages(['pesos' => 'Uno de los tipos de evaluación no existe.']);
                }
                if (filled($peso)) {
                    $nuevos[(int) $tipo] = TrxCalificacion::centesimos($peso);
                }
            }
            $suma = array_sum($nuevos);
            if ($suma !== self::PESO_TOTAL) {
                throw ValidationException::withMessages(['pesos' => 'Los pesos deben sumar exactamente 100 % (ahora suman '.TrxCalificacion::formatear($suma).' %).']);
            }

            $errores = [];
            foreach ($actuales as $tipo => $evaluacion) {
                $peso = $nuevos[$tipo] ?? null;
                if ($evaluacion->estaBloqueada() && $peso !== TrxCalificacion::centesimos($evaluacion->peso_porcentaje)) {
                    $errores["pesos.{$tipo}"] = $tipos[$tipo]->nombre.($evaluacion->cerrada ? ' está cerrada' : ' ya tiene calificaciones')
                        .': su peso ('.$evaluacion->peso_porcentaje.' %) no se cambia y no se puede quitar.';
                }
            }
            if ($errores) {
                throw ValidationException::withMessages($errores);
            }

            foreach ($actuales as $tipo => $evaluacion) {
                if (! isset($nuevos[$tipo])) {
                    $evaluacion->delete();
                } elseif ($nuevos[$tipo] !== TrxCalificacion::centesimos($evaluacion->peso_porcentaje)) {
                    $evaluacion->update(['peso_porcentaje' => TrxCalificacion::formatear($nuevos[$tipo])]);
                }
            }
            foreach (array_diff_key($nuevos, $actuales->all()) as $tipo => $peso) {
                $materia->evaluaciones()->create(['cat_tipo_evaluacion_id' => $tipo, 'peso_porcentaje' => TrxCalificacion::formatear($peso)]);
            }
            $materia->unsetRelation('evaluaciones');
        });
    }

    // Los tipos con aplica_promedio, en partes iguales; el redondeo se ajusta en el último (33.33, 33.33, 33.34).
    public static function pesosEstandar(): array
    {
        $tipos = CatTipoEvaluacion::where('aplica_promedio', true)->ordenados()->pluck('id')->all();
        if ($tipos === []) {
            return [];
        }
        $base = intdiv(self::PESO_TOTAL, count($tipos));
        $pesos = array_fill_keys($tipos, $base);
        $pesos[end($tipos)] = self::PESO_TOTAL - $base * (count($tipos) - 1);

        return array_map(fn (int $peso) => TrxCalificacion::formatear($peso), $pesos);
    }

    public static function aplicarEsquemaEstandar(AcaGrupoAsignatura $materia): void
    {
        $pesos = self::pesosEstandar();
        if ($pesos === []) {
            throw ValidationException::withMessages(['accion' => 'No hay tipos de evaluación que cuenten para el promedio.']);
        }
        try {
            // Los tipos que no están en el esquema se quitan (si se puede).
            static::programar($materia, $pesos + array_fill_keys(CatTipoEvaluacion::pluck('id')->all(), null));
        } catch (ValidationException $excepcion) {
            throw ValidationException::withMessages(['accion' => 'No se pudo aplicar el esquema estándar. '.collect($excepcion->errors())->flatten()->implode(' ')]);
        }
    }

    // Exige que todos los alumnos INSCRITO tengan calificación. Después solo se modifica con calificaciones.corregir.
    public function cerrar(): void
    {
        PeriodoDeTrabajo::exigir(PeriodoDeTrabajo::OPERAR, $this->materia->periodoEscolar());
        DB::transaction(function () {
            static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $this->refresh();
            $nombre = $this->tipo->nombre;
            if ($this->cerrada) {
                throw ValidationException::withMessages(['accion' => "{$nombre} ya está cerrada."]);
            }
            $faltantes = $this->inscripcionesSinCalificacion()->count();
            if ($faltantes > 0) {
                throw ValidationException::withMessages(['accion' => "No se puede cerrar {$nombre}: ".($faltantes === 1
                    ? 'falta calificar a 1 alumno (inscrito o recursante).' : "faltan {$faltantes} alumnos (inscritos o recursantes) por calificar.").' Guarda las calificaciones antes de cerrar.']);
            }
            $this->forceFill(['cerrada' => true])->save();
        });
    }
}
