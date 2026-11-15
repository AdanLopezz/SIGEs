<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\PeriodoDeTrabajo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Recursamiento de una materia reprobada en otro grupo (periodo posterior). Las calificaciones y el resultado del
 * recursamiento usan la inscripción del resultado reprobado (la de origen) con las evaluaciones de la materia destino.
 */
class AcaRecursamiento extends Model
{
    use Auditable;

    protected $table = 'aca_recursamiento';

    // Todos los campos los pone TrxResultadoAsignatura::asignarRecursamiento().
    protected $fillable = [];

    protected function casts(): array
    {
        return ['trx_resultado_asignatura_id' => 'integer', 'aca_grupo_asignatura_id' => 'integer', 'fecha_inicio' => 'date'];
    }

    // El resultado reprobado que se recursa.
    public function origen(): BelongsTo
    {
        return $this->belongsTo(TrxResultadoAsignatura::class, 'trx_resultado_asignatura_id');
    }

    // La materia (de otro grupo) donde se recursa.
    public function materia(): BelongsTo
    {
        return $this->belongsTo(AcaGrupoAsignatura::class, 'aca_grupo_asignatura_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }

    public function scopeDelAlumno(Builder $consulta, int $alumno): Builder
    {
        return $consulta->whereHas('origen.inscripcion', fn (Builder $inscripciones) => $inscripciones->where('per_alumno_id', $alumno));
    }

    public function scopeDelPeriodoActivo(Builder $consulta): Builder
    {
        return $consulta->whereHas('materia.grupo', fn (Builder $grupos) => $grupos->delPeriodoActivo());
    }

    // Recursamientos cursados en el periodo de consulta (o en $periodo).
    public function scopeDelPeriodoConsulta(Builder $consulta, ?int $periodo = null): Builder
    {
        return $consulta->whereHas('materia.grupo', fn (Builder $grupos) => $grupos->where('aca_periodo_escolar_id', $periodo ?? PeriodoDeTrabajo::idConsulta()));
    }

    // Recursamientos de materias reprobadas en $periodo (el origen es de ese periodo; se cursan después).
    public function scopeDeReprobadasEn(Builder $consulta, int $periodo): Builder
    {
        return $consulta->whereHas('origen.materia.grupo', fn (Builder $grupos) => $grupos->where('aca_periodo_escolar_id', $periodo));
    }

    // Inscripción con la que se califica (la del resultado reprobado).
    public function inscripcionId(): int
    {
        return $this->origen->aca_inscripcion_id;
    }

    // Resultado del recursamiento en la materia destino (null mientras está en curso). Se precarga con cargarResultados().
    public function resultadoDestino(): ?TrxResultadoAsignatura
    {
        if (! $this->relationLoaded('resultadoDestino')) {
            static::cargarResultados(collect([$this]));
        }

        return $this->getRelation('resultadoDestino');
    }

    // Precarga el resultado destino de varios recursamientos con una sola consulta (la llave es compuesta: inscripción + materia).
    public static function cargarResultados(Collection $recursamientos): void
    {
        // Puede llegar una colección simple (p. ej. de flatMap); se convierte para cargar relaciones.
        $recursamientos = new EloquentCollection($recursamientos->all());
        $recursamientos->loadMissing('origen');
        $resultados = TrxResultadoAsignatura::whereIn('aca_inscripcion_id', $recursamientos->map->inscripcionId()->unique())
            ->whereIn('aca_grupo_asignatura_id', $recursamientos->pluck('aca_grupo_asignatura_id')->unique())->get()
            ->keyBy(fn (TrxResultadoAsignatura $resultado) => $resultado->aca_inscripcion_id.'-'.$resultado->aca_grupo_asignatura_id);
        foreach ($recursamientos as $recursamiento) {
            $recursamiento->setRelation('resultadoDestino', $resultados->get($recursamiento->inscripcionId().'-'.$recursamiento->aca_grupo_asignatura_id));
        }
    }

    public function tieneCalificaciones(): bool
    {
        return TrxCalificacion::where('aca_inscripcion_id', $this->inscripcionId())
            ->whereIn('aca_evaluacion_id', AcaEvaluacion::where('aca_grupo_asignatura_id', $this->aca_grupo_asignatura_id)->select('id'))->exists();
    }

    // Solo si el recursante aún no tiene calificaciones ni resultado en la materia destino (para corregir una asignación equivocada).
    public function quitar(): void
    {
        DB::transaction(function () {
            static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            if ($this->tieneCalificaciones() || $this->resultadoDestino()) {
                throw ValidationException::withMessages(['accion' => 'No se puede quitar el recursamiento: el alumno ya tiene calificaciones en la materia destino.']);
            }
            $this->delete();
        });
    }
}
