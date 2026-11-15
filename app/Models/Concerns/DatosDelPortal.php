<?php

namespace App\Models\Concerns;

use App\Models\AcaInscripcion;
use App\Models\TrxAsistenciaDiaria;
use App\Models\TrxIncidencia;
use App\Models\TrxPermisoSalida;
use App\Models\TrxResultadoAsignatura;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Consultas del portal de Alumno y Padre de familia (PerAlumno). Solo lectura.
 * Quién puede ver a qué alumno lo decide App\Policies\PortalPolicy.
 */
trait DatosDelPortal
{
    // Alumnos de los que la persona es tutor ACTIVO.
    public function scopeDeTutor(Builder $consulta, ?int $persona): Builder
    {
        return $consulta->whereHas('tutores', fn (Builder $tutores) => $tutores->activos()->where('per_persona_id', $persona ?? 0));
    }

    // El alumno de la persona de la cuenta ("Mi escuela").
    public static function deLaCuenta(User $usuario): ?self
    {
        return $usuario->per_persona_id ? static::where('per_persona_id', $usuario->per_persona_id)->first() : null;
    }

    /**
     * Inscripción de un periodo: la INSCRITO o, si ya no la hay (baja, cambio de grupo), la más reciente del periodo.
     * Sin $periodo, la del periodo activo (inicio del portal); las pantallas pasan el de consulta.
     */
    public function inscripcionDelPeriodo(?int $periodo = null): ?AcaInscripcion
    {
        if ($periodo === null) {
            return $this->inscripcionActual
                ?? $this->inscripciones()->delPeriodoActivo()->orderByDesc('fecha_inscripcion')->orderByDesc('id')->first();
        }

        return $this->inscripciones()->delPeriodoConsulta($periodo)
            ->orderByRaw('CASE WHEN estado = ? THEN 0 ELSE 1 END', [AcaInscripcion::INSCRITO])
            ->orderByDesc('fecha_inscripcion')->orderByDesc('id')->first();
    }

    // Resultados (ordinario, extraordinario o recursamiento) de materias del periodo activo.
    public function resultadosDelPeriodo(): Builder
    {
        return TrxResultadoAsignatura::whereHas('inscripcion', fn (Builder $inscripciones) => $inscripciones->where('per_alumno_id', $this->id))
            ->whereHas('materia.grupo', fn (Builder $grupos) => $grupos->delPeriodoActivo());
    }

    /**
     * Resumen del inicio del portal. El promedio es el de las definitivas ya cerradas del periodo activo, a 1 decimal
     * (null si aún no hay resultados); las faltas son las del mes en curso.
     */
    public function resumenPortal(): array
    {
        $promedio = $this->resultadosDelPeriodo()->avg('calificacion');

        return [
            'promedio' => $promedio === null ? null : number_format(round((float) $promedio, 1), 1, '.', ''),
            'faltas_mes' => TrxAsistenciaDiaria::whereHas('inscripcion', fn (Builder $inscripciones) => $inscripciones->where('per_alumno_id', $this->id))
                ->where('estado', TrxAsistenciaDiaria::FALTA)
                ->whereBetween('fecha', [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()])->count(),
            'incidencias_abiertas' => TrxIncidencia::deAlumno($this->id)->where('estado', '!=', TrxIncidencia::CERRADA)->count(),
            'permisos_pendientes' => TrxPermisoSalida::deAlumno($this->id)->where('estado', TrxPermisoSalida::PENDIENTE)->count(),
        ];
    }

    // Todos los resultados del alumno agrupados por periodo (clave), del más reciente al más antiguo.
    public function historialDeResultados(): Collection
    {
        return TrxResultadoAsignatura::with(['materia.asignatura', 'materia.grupo.periodo'])
            ->whereHas('inscripcion', fn (Builder $inscripciones) => $inscripciones->where('per_alumno_id', $this->id))->get()
            ->sortBy([fn ($a, $b) => $b->materia->grupo->periodo->fecha_inicio <=> $a->materia->grupo->periodo->fecha_inicio,
                fn ($a, $b) => $a->materia->asignatura->nombre <=> $b->materia->asignatura->nombre])
            ->groupBy(fn (TrxResultadoAsignatura $resultado) => $resultado->materia->grupo->periodo->clave);
    }

    // [AAAA-MM-DD => estado] de la asistencia del mes, de cualquiera de sus inscripciones.
    public function asistenciaDelMes(CarbonInterface $mes): Collection
    {
        return TrxAsistenciaDiaria::whereHas('inscripcion', fn (Builder $inscripciones) => $inscripciones->where('per_alumno_id', $this->id))
            ->whereBetween('fecha', [$mes->copy()->startOfMonth()->toDateString(), $mes->copy()->endOfMonth()->toDateString()])
            ->get()->mapWithKeys(fn (TrxAsistenciaDiaria $asistencia) => [$asistencia->fecha->toDateString() => $asistencia->estado]);
    }
}
