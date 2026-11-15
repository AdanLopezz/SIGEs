<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\EliminaSiNoEstaEnUso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Support\PeriodoDeTrabajo;

class AcaGrupoEscolar extends Model
{
    use Auditable;

    use EliminaSiNoEstaEnUso { eliminar as private eliminarSinReferencias; }

    protected $table = 'aca_grupo_escolar';

    protected $fillable = ['aca_periodo_escolar_id', 'aca_carrera_id', 'aca_turno_id', 'identificador', 'semestre', 'orientador_id', 'tutor_id'];

    protected function casts(): array
    {
        return ['semestre' => 'integer'];
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(AcaPeriodoEscolar::class, 'aca_periodo_escolar_id');
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(AcaCarrera::class, 'aca_carrera_id');
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(AcaTurno::class, 'aca_turno_id');
    }

    public function orientador(): BelongsTo
    {
        return $this->belongsTo(PerPersonal::class, 'orientador_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(PerPersonal::class, 'tutor_id');
    }

    public function materias(): HasMany
    {
        return $this->hasMany(AcaGrupoAsignatura::class, 'aca_grupo_escolar_id');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(AcaInscripcion::class, 'aca_grupo_escolar_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return implode(' · ', array_filter([$this->identificador, $this->carrera?->nombre, $this->turno?->nombre]));
    }

    public function scopeDelPeriodo(Builder $consulta, ?int $periodo): Builder
    {
        return $periodo ? $consulta->where('aca_periodo_escolar_id', $periodo) : $consulta;
    }

    public function scopeDelPeriodoActivo(Builder $consulta): Builder
    {
        return $consulta->whereHas('periodo', fn (Builder $periodos) => $periodos->where('activo', true));
    }

    // Grupos del periodo de consulta (selector de la barra superior).
    public function scopeDelPeriodoConsulta(Builder $consulta): Builder
    {
        return $consulta->where('aca_grupo_escolar.aca_periodo_escolar_id', PeriodoDeTrabajo::idConsulta());
    }

    /**
     * Asistencia: todos para los roles de PerAlumno::ROLES_VEN_TODOS; si no, donde es orientador, tutor o docente.
     * Del periodo activo (registrar incidencias) o, con $periodo, de ese periodo (consultas).
     */
    public function scopeVisiblesEnAsistencia(Builder $consulta, User $usuario, int|array|null $periodo = null): Builder
    {
        $periodo === null ? $consulta->delPeriodoActivo() : $consulta->whereIn('aca_grupo_escolar.aca_periodo_escolar_id', (array) $periodo);
        if ($usuario->hasAnyRole(PerAlumno::ROLES_VEN_TODOS)) {
            return $consulta;
        }
        $personal = $usuario->persona?->personal;
        if (! $personal?->activo) {
            return $consulta->whereRaw('1 = 0');
        }

        return $consulta->where(fn (Builder $grupos) => $grupos->where('orientador_id', $personal->id)->orWhere('tutor_id', $personal->id)
            ->orWhereHas('materias', fn (Builder $materias) => $materias->where('docente_id', $personal->id)));
    }

    /**
     * Grupos que el usuario acompaña como orientador o tutor (sin docentes ni Prefectura): Seguimiento del alumno.
     * Todos para TrxSeguimientoAlumno::ROLES_VEN_TODOS. $periodo null = el activo.
     */
    public function scopeAcompanadosPor(Builder $consulta, User $usuario, int|array|null $periodo = null): Builder
    {
        $periodo === null ? $consulta->delPeriodoActivo() : $consulta->whereIn('aca_grupo_escolar.aca_periodo_escolar_id', (array) $periodo);
        if ($usuario->hasAnyRole(TrxSeguimientoAlumno::ROLES_VEN_TODOS)) {
            return $consulta;
        }
        $personal = $usuario->persona?->personal;
        if (! $personal?->activo) {
            return $consulta->whereRaw('1 = 0');
        }

        return $consulta->where(fn (Builder $grupos) => $grupos->where('orientador_id', $personal->id)->orWhere('tutor_id', $personal->id));
    }

    public function scopeDeCarrera(Builder $consulta, ?int $carrera): Builder
    {
        return $carrera ? $consulta->where('aca_carrera_id', $carrera) : $consulta;
    }

    public function scopeDeTurno(Builder $consulta, ?int $turno): Builder
    {
        return $turno ? $consulta->where('aca_turno_id', $turno) : $consulta;
    }

    public function scopeDeSemestre(Builder $consulta, ?int $semestre): Builder
    {
        return $semestre ? $consulta->where('semestre', $semestre) : $consulta;
    }

    public function scopeOrdenados(Builder $consulta): Builder
    {
        return $consulta->orderBy('semestre')->orderBy('identificador')->orderBy('id');
    }

    // Requiere la carrera. El último semestre no se reinscribe: sus alumnos son candidatos a egreso.
    public function esUltimoSemestre(): bool
    {
        return $this->semestre >= (int) ($this->carrera?->total_semestres ?? 6);
    }

    /**
     * Identificador propuesto para el grupo del semestre $semestre (Cambio de periodo): el primer número se sustituye por
     * el semestre nuevo (1A → 2A, TV1A → TV2A); si no tiene número, se antepone (A → 2A). Solo 0-9 y A-Z, máximo 20.
     */
    public static function identificadorPara(string $identificador, int $semestre): string
    {
        $base = preg_replace('/[^0-9A-Z]/', '', mb_strtoupper($identificador));
        $nuevo = preg_match('/\d+/', $base) ? preg_replace('/\d+/', (string) $semestre, $base, 1) : $semestre.$base;

        return mb_substr($nuevo, 0, 20);
    }

    public function tieneAlumnos(): bool
    {
        return $this->exists && $this->inscripciones()->exists();
    }

    public function tieneMaterias(): bool
    {
        return $this->exists && $this->materias()->exists();
    }

    // Grupos, materias y docentes se preparan en el periodo activo y en los futuros; los de un periodo pasado no cambian.
    public function exigirPreparable(): void
    {
        PeriodoDeTrabajo::exigir(PeriodoDeTrabajo::PREPARAR, $this->periodo()->first());
    }

    public function eliminar(): bool
    {
        $this->exigirPreparable();

        return $this->eliminarSinReferencias();
    }

    public function guardar(array $datos): void
    {
        DB::transaction(function () use ($datos) {
            PeriodoDeTrabajo::exigir(PeriodoDeTrabajo::PREPARAR, AcaPeriodoEscolar::find($datos['aca_periodo_escolar_id'] ?? $this->aca_periodo_escolar_id), 'aca_periodo_escolar_id');
            if ($this->exists) {
                $this->exigirPreparable();
                static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
                $this->refresh();
                $periodo = (int) ($datos['aca_periodo_escolar_id'] ?? $this->aca_periodo_escolar_id);
                if ($periodo !== (int) $this->aca_periodo_escolar_id && $this->tieneAlumnos()) {
                    throw ValidationException::withMessages(['aca_periodo_escolar_id' => 'No se puede cambiar el periodo porque el grupo ya tiene alumnos.']);
                }
                if ((int) $datos['semestre'] !== $this->semestre && $this->tieneMaterias()) {
                    throw ValidationException::withMessages(['semestre' => 'No se puede cambiar el semestre porque el grupo ya tiene materias.']);
                }
            }
            $this->fill($datos)->save();
        });
    }
}
