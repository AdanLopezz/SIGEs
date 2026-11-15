<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\PeriodoDeTrabajo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AcaInscripcion extends Model
{
    use Auditable;

    public const INSCRITO = 'INSCRITO';

    public const BAJA = 'BAJA';

    public const CAMBIO_GRUPO = 'CAMBIO_GRUPO';

    public const ESTADOS = [self::INSCRITO => 'Inscrito', self::BAJA => 'Baja', self::CAMBIO_GRUPO => 'Cambio de grupo'];

    protected $table = 'aca_inscripcion';

    protected $fillable = ['aca_grupo_escolar_id', 'estado', 'fecha_inscripcion'];

    protected function casts(): array
    {
        return ['fecha_inscripcion' => 'date'];
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(PerAlumno::class, 'per_alumno_id');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(AcaGrupoEscolar::class, 'aca_grupo_escolar_id');
    }

    public function baja(): HasOne
    {
        return $this->hasOne(AcaBaja::class, 'aca_inscripcion_id');
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(TrxCalificacion::class, 'aca_inscripcion_id');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(TrxResultadoAsignatura::class, 'aca_inscripcion_id');
    }

    /**
     * Boleta de la inscripción: materias del grupo, tipos de evaluación usados (columnas), calificaciones por evaluación
     * y resultados por materia. La vista solo muestra las calificaciones de evaluaciones cerradas.
     */
    public function boleta(): array
    {
        $materias = $this->grupo->materias()->with(['asignatura', 'evaluaciones.tipo'])->get()->sortBy('asignatura.nombre')->values();

        return [
            'materias' => $materias,
            'tipos' => $materias->flatMap->evaluaciones->pluck('tipo')->unique('id')->sortBy('orden')->values(),
            'calificaciones' => $this->calificaciones()->get()->keyBy('aca_evaluacion_id'),
            'resultados' => $this->resultados()->get()->keyBy('aca_grupo_asignatura_id'),
        ];
    }

    public function scopeInscritas(Builder $consulta): Builder
    {
        return $consulta->where('estado', self::INSCRITO);
    }

    public function scopeDelPeriodoActivo(Builder $consulta): Builder
    {
        return $consulta->whereHas('grupo', fn (Builder $grupos) => $grupos->delPeriodoActivo());
    }

    // Del periodo de consulta (selector de la barra superior) o de $periodo.
    public function scopeDelPeriodoConsulta(Builder $consulta, ?int $periodo = null): Builder
    {
        return $consulta->whereHas('grupo', fn (Builder $grupos) => $grupos->where('aca_periodo_escolar_id', $periodo ?? PeriodoDeTrabajo::idConsulta()));
    }

    public function getEstadoNombreAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }
}
