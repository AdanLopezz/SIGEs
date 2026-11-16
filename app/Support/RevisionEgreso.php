<?php

namespace App\Support;

use App\Models\AcaInscripcion;
use App\Models\PerAlumno;
use App\Models\TrxResultadoAsignatura;
use Illuminate\Database\Eloquent\Builder;

/** Revisa el historial registrado; la validación del historial oficial corresponde a Control Escolar. */
class RevisionEgreso
{
    public static function inscripcionesFinales(Builder $inscripciones): void
    {
        $inscripciones->whereIn('estado', [AcaInscripcion::INSCRITO, AcaInscripcion::EGRESADO])
            ->whereHas('grupo', function (Builder $grupos) {
                $grupos->whereColumn('aca_grupo_escolar.aca_carrera_id', 'per_alumno.aca_carrera_id')
                    ->whereHas('carrera', fn (Builder $carreras) => $carreras->whereColumn('aca_grupo_escolar.semestre', '>=', 'aca_carrera.total_semestres'))
                    ->whereHas('periodo', fn (Builder $periodos) => $periodos->whereDate('fecha_inicio', '<=', today()));
            });
    }

    public static function consulta(array $filtros): Builder
    {
        $egresados = ($filtros['lista'] ?? 'candidatos') === 'egresados';
        $consulta = PerAlumno::query()->with(['persona', 'carrera', 'estado', 'egreso'])
            ->when($filtros['carrera'] ?? null, fn ($alumnos, $carrera) => $alumnos->where('aca_carrera_id', $carrera))
            ->buscar($filtros['buscar'] ?? null);
        $egresados ? $consulta->whereHas('egreso') : $consulta->whereDoesntHave('egreso')->whereHas('estado', fn ($estados) => $estados->where('nombre', 'Activo'));
        // En egresados, sin filtros escolares se incluyen también los registros históricos importados.
        if (! $egresados || ! empty($filtros['periodo']) || ! empty($filtros['grupo'])) {
            $consulta->whereHas('inscripciones', function (Builder $inscripciones) use ($filtros) {
                static::inscripcionesFinales($inscripciones);
                $inscripciones->when($filtros['grupo'] ?? null, fn ($i, $grupo) => $i->where('aca_grupo_escolar_id', $grupo))
                    ->when($filtros['periodo'] ?? null, fn ($i, $periodo) => $i->whereHas('grupo', fn ($g) => $g->where('aca_periodo_escolar_id', $periodo)));
            });
        }

        return $consulta->orderBy('matricula')->orderBy('id');
    }

    public static function academica(PerAlumno $alumno): array
    {
        $inscripciones = $alumno->inscripciones()->whereIn('estado', [AcaInscripcion::INSCRITO, AcaInscripcion::EGRESADO])
            ->whereHas('grupo', fn ($grupos) => $grupos->where('aca_carrera_id', $alumno->aca_carrera_id)
                ->whereHas('periodo', fn ($periodos) => $periodos->whereDate('fecha_inicio', '<=', today())))
            ->with(['grupo.carrera', 'grupo.periodo', 'grupo.materias.asignatura', 'grupo.materias.evaluaciones', 'resultados'])->get();
        $final = $inscripciones->filter(fn ($i) => $i->grupo->esUltimoSemestre())
            ->sortByDesc(fn ($i) => $i->grupo->periodo->fecha_inicio->format('Y-m-d').' '.str_pad($i->id, 20, '0', STR_PAD_LEFT))->first();
        $pendientes = collect();
        foreach ($inscripciones as $inscripcion) {
            if ($inscripcion->grupo->materias->isEmpty()) {
                $pendientes->push(['grupo' => $inscripcion->grupo->identificador, 'materia' => 'Sin materias asignadas', 'motivo' => 'El grupo no tiene carga académica registrada.']);
            }
            foreach ($inscripcion->grupo->materias as $materia) {
                $resultado = $inscripcion->resultados->firstWhere('aca_grupo_asignatura_id', $materia->id);
                if (! $resultado || $materia->evaluaciones->isEmpty() || $materia->evaluaciones->contains(fn ($e) => ! $e->cerrada)) {
                    $pendientes->push(['grupo' => $inscripcion->grupo->identificador, 'materia' => $materia->asignatura->nombre, 'motivo' => 'Falta resultado definitivo o cierre de evaluaciones.']);
                }
            }
        }
        $adeudos = TrxResultadoAsignatura::sinRegularizar()->whereHas('inscripcion', fn ($i) => $i->where('per_alumno_id', $alumno->id))
            ->with(['materia.asignatura', 'materia.grupo'])->get();
        foreach ($adeudos as $adeudo) {
            $pendientes->push(['grupo' => $adeudo->materia->grupo->identificador, 'materia' => $adeudo->materia->asignatura->nombre, 'motivo' => 'Materia reprobada sin regularizar.']);
        }
        $recursamientos = \App\Models\AcaRecursamiento::whereHas('origen.inscripcion', fn ($i) => $i->where('per_alumno_id', $alumno->id))
            ->with(['materia.evaluaciones', 'materia.asignatura', 'materia.grupo'])->get();
        foreach ($recursamientos as $recursamiento) {
            $materia = $recursamiento->materia;
            if ($materia->evaluaciones->isEmpty() || $materia->evaluaciones->contains(fn ($e) => ! $e->cerrada)) {
                $pendientes->push(['grupo' => $materia->grupo->identificador, 'materia' => $materia->asignatura->nombre, 'motivo' => 'El recursamiento tiene evaluaciones sin cerrar.']);
            }
        }

        return ['inscripcion_final' => $final, 'pendientes' => $pendientes,
            'semestres_registrados' => $inscripciones->pluck('grupo.semestre')->unique()->sort()->values(),
            'adeudos' => $adeudos->count()];
    }
}
