<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\EliminaSiNoEstaEnUso;
use App\Policies\GrupoAsignaturaPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Support\PeriodoDeTrabajo;

#[UsePolicy(GrupoAsignaturaPolicy::class)]
class AcaGrupoAsignatura extends Model
{
    use Auditable;

    use EliminaSiNoEstaEnUso { eliminar as private eliminarSinReferencias; }

    // Ven las evaluaciones y calificaciones de todas las materias; los demás, solo aquellas en las que son docentes.
    public const ROLES_VEN_TODAS = ['Administrador', 'Control Escolar'];

    protected $table = 'aca_grupo_asignatura';

    protected $fillable = ['aca_asignatura_id', 'docente_id'];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(AcaGrupoEscolar::class, 'aca_grupo_escolar_id');
    }

    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(AcaAsignatura::class, 'aca_asignatura_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(PerPersonal::class, 'docente_id');
    }

    public function evaluaciones(): HasMany
    {
        // Ordenadas por el tipo (Parcial 1, 2, 3…).
        return $this->hasMany(AcaEvaluacion::class, 'aca_grupo_asignatura_id')
            ->orderBy(CatTipoEvaluacion::select('orden')->whereColumn('cat_tipo_evaluacion.id', 'aca_evaluacion.cat_tipo_evaluacion_id'))->orderBy('id');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(TrxResultadoAsignatura::class, 'aca_grupo_asignatura_id');
    }

    // Recursantes de otros grupos que cursan aquí la materia.
    public function recursamientos(): HasMany
    {
        return $this->hasMany(AcaRecursamiento::class, 'aca_grupo_asignatura_id');
    }

    /**
     * Inscripciones que se califican en la materia: [inscripción => oportunidad del resultado].
     * ORDINARIO: las INSCRITO del grupo. RECURSAMIENTO: la inscripción de origen de cada recursante con el alumno Activo
     * (si el alumno se dio de baja, su fila queda en solo lectura y no cuenta para cerrar).
     */
    public function inscripcionesCalificables(): array
    {
        $inscritas = $this->grupo->inscripciones()->inscritas()->pluck('id')
            ->mapWithKeys(fn (int $inscripcion) => [$inscripcion => TrxResultadoAsignatura::ORDINARIO])->all();
        $recursantes = $this->recursamientos()->with('origen.inscripcion.alumno.estado')->get()
            ->filter(fn (AcaRecursamiento $recursamiento) => $recursamiento->origen->inscripcion->alumno->estaActivo())
            ->mapWithKeys(fn (AcaRecursamiento $recursamiento) => [$recursamiento->inscripcionId() => TrxResultadoAsignatura::RECURSAMIENTO])->all();

        return $inscritas + $recursantes;
    }

    // Clases del horario semanal (aca_horario).
    public function horarios(): HasMany
    {
        return $this->hasMany(AcaHorario::class, 'aca_grupo_asignatura_id');
    }

    // Días de la semana (1 = lunes … 6 = sábado) en los que la materia tiene clase según el horario.
    public function diasDeClase(): array
    {
        return $this->horarios()->distinct()->orderBy('dia_semana')->pluck('dia_semana')->map(fn ($dia) => (int) $dia)->all();
    }

    // El usuario es el docente (activo) de la materia. Para el pase de lista no cuentan los ROLES_VEN_TODAS.
    public function esDelDocente(User $usuario): bool
    {
        $personal = $usuario->persona?->personal;

        return (bool) $personal?->activo && (int) $this->docente_id === (int) $personal->id;
    }

    public function getNombreAttribute(): string
    {
        return trim(($this->asignatura?->nombre ?? '').' · '.($this->grupo?->identificador ?? ''), ' ·');
    }

    // Materias de grupo (y docentes): solo en un periodo que se puede preparar (activo o futuro).
    public static function agregarA(AcaGrupoEscolar $grupo, array $datos): self
    {
        $grupo->exigirPreparable();

        return $grupo->materias()->create($datos);
    }

    // Si la materia ya tiene horario, el nuevo docente no debe tener otra clase a la misma hora en el periodo.
    public function actualizarDocente(array $datos): void
    {
        $this->grupo->exigirPreparable();
        DB::transaction(function () use ($datos) {
            if (isset($datos['docente_id']) && (int) $datos['docente_id'] !== (int) $this->docente_id) {
                AcaHorario::exigirDocenteLibre($this, (int) $datos['docente_id']);
            }
            $this->update($datos);
        });
    }

    public function eliminar(): bool
    {
        $this->grupo->exigirPreparable();

        return $this->eliminarSinReferencias();
    }

    // Periodo de la materia (el de su grupo).
    public function periodoEscolar(): ?AcaPeriodoEscolar
    {
        return $this->grupo()->with('periodo')->first()?->periodo;
    }

    /**
     * Todas para ROLES_VEN_TODAS; si no, las materias en las que el usuario es el docente.
     * Sin $periodo: las del periodo de consulta (listados). Las acciones las limita GrupoAsignaturaPolicy por el periodo de la materia.
     */
    public function scopeVisiblesParaCalificar(Builder $consulta, User $usuario, ?int $periodo = null): Builder
    {
        $periodo ??= PeriodoDeTrabajo::idConsulta();
        $consulta->whereHas('grupo', fn (Builder $grupos) => $grupos->where('aca_periodo_escolar_id', $periodo));

        return $consulta->deResponsable($usuario);
    }

    // Sin filtro de periodo: la materia es del usuario (o el usuario ve todas).
    public function scopeDeResponsable(Builder $consulta, User $usuario): Builder
    {
        if ($usuario->hasAnyRole(self::ROLES_VEN_TODAS)) {
            return $consulta;
        }
        $personal = $usuario->persona?->personal;

        return $personal?->activo ? $consulta->where('docente_id', $personal->id) : $consulta->whereRaw('1 = 0');
    }

    // En cualquier periodo (consulta); qué se puede hacer depende del periodo (GrupoAsignaturaPolicy).
    public function esVisibleParaCalificar(User $usuario): bool
    {
        return static::whereKey($this->getKey())->deResponsable($usuario)->exists();
    }

    public function scopeDeGrupo(Builder $consulta, ?int $grupo): Builder
    {
        return $grupo ? $consulta->where('aca_grupo_escolar_id', $grupo) : $consulta;
    }

    public function scopeDeAsignatura(Builder $consulta, ?int $asignatura): Builder
    {
        return $asignatura ? $consulta->where('aca_asignatura_id', $asignatura) : $consulta;
    }

    // Por grupo (semestre e identificador) y luego por nombre de la asignatura.
    public function scopeOrdenados(Builder $consulta): Builder
    {
        $delGrupo = fn (string $columna) => AcaGrupoEscolar::select($columna)->whereColumn('aca_grupo_escolar.id', 'aca_grupo_asignatura.aca_grupo_escolar_id');

        return $consulta->orderBy($delGrupo('semestre'))->orderBy($delGrupo('identificador'))
            ->orderBy(AcaAsignatura::select('nombre')->whereColumn('aca_asignatura.id', 'aca_grupo_asignatura.aca_asignatura_id'))->orderBy('id');
    }

    public function sumaDePesos(?Collection $evaluaciones = null): int
    {
        return ($evaluaciones ?? $this->evaluaciones)->sum(fn (AcaEvaluacion $evaluacion) => TrxCalificacion::centesimos($evaluacion->peso_porcentaje));
    }

    // Lo que falta, del lado de las evaluaciones, para cerrar los resultados.
    public function pendientesDeCierre(?Collection $evaluaciones = null): array
    {
        $evaluaciones ??= $this->evaluaciones;
        if ($evaluaciones->isEmpty()) {
            return ['No tiene evaluaciones programadas.'];
        }
        $pendientes = [];
        $suma = $this->sumaDePesos($evaluaciones);
        if ($suma !== AcaEvaluacion::PESO_TOTAL) {
            $pendientes[] = 'Los pesos suman '.TrxCalificacion::formatear($suma).' % (deben sumar 100 %).';
        }
        $abiertas = $evaluaciones->reject->cerrada;
        if ($abiertas->isNotEmpty()) {
            $pendientes[] = 'Falta cerrar: '.$abiertas->map(fn (AcaEvaluacion $evaluacion) => $evaluacion->tipo->nombre)->implode(', ').'.';
        }

        return $pendientes;
    }
}
