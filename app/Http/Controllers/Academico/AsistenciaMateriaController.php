<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\FiltrarAsistenciaMateriaRequest;
use App\Models\AcaAsignatura;
use App\Models\AcaGrupoAsignatura;
use App\Models\AcaGrupoEscolar;
use App\Models\AcaInscripcion;
use App\Models\TrxAsistenciaClase;
use App\Support\Paginacion;
use App\Support\PeriodoDeTrabajo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Asistencia por materia (periodo de consulta): el docente ve sus materias (asistencia_clase.registrar) y Control Escolar
 * todas (asistencia_clase.corregir). La tabla de una materia es alumnos × fechas del mes, con totales y porcentaje del periodo.
 */
class AsistenciaMateriaController extends Controller
{
    public function index(FiltrarAsistenciaMateriaRequest $solicitud)
    {
        $filtros = $solicitud->validated();
        $visibles = function () use ($solicitud): Builder {
            $consulta = AcaGrupoAsignatura::whereHas('grupo', fn (Builder $grupos) => $grupos->where('aca_periodo_escolar_id', PeriodoDeTrabajo::idConsulta()));
            if (! $solicitud->user()->can('asistencia_clase.corregir')) {
                $personal = $solicitud->user()->persona?->personal;
                $consulta->where('docente_id', $personal?->activo ? $personal->id : 0);
            }

            return $consulta;
        };
        $grupos = AcaGrupoEscolar::with(['carrera', 'turno'])->whereIn('id', $visibles()->select('aca_grupo_escolar_id'))->ordenados()->get();
        $asignaturas = AcaAsignatura::whereIn('id', $visibles()->deGrupo(isset($filtros['grupo']) ? (int) $filtros['grupo'] : null)->select('aca_asignatura_id'))->ordenados()->get();
        $materias = $visibles()->with(['grupo.carrera', 'grupo.turno', 'asignatura', 'docente.persona', 'horarios'])
            ->deGrupo(isset($filtros['grupo']) ? (int) $filtros['grupo'] : null)
            ->deAsignatura(isset($filtros['asignatura']) ? (int) $filtros['asignatura'] : null)
            ->ordenados()->paginarListado();
        $ids = $materias->pluck('id')->all();
        $listas = TrxAsistenciaClase::whereIn('aca_grupo_asignatura_id', $ids)->groupBy('aca_grupo_asignatura_id')
            ->selectRaw('aca_grupo_asignatura_id, count(distinct fecha) as listas')->pluck('listas', 'aca_grupo_asignatura_id');
        $bajoMinimo = TrxAsistenciaClase::resumen()->bajoMinimo()->whereIn('trx_asistencia_clase.aca_grupo_asignatura_id', $ids)->get()->countBy('aca_grupo_asignatura_id');
        $minimo = TrxAsistenciaClase::minimoPorcentaje();

        return view('academico.asistencia-materia.index', compact('materias', 'grupos', 'asignaturas', 'filtros', 'listas', 'bajoMinimo', 'minimo'));
    }

    public function show(FiltrarAsistenciaMateriaRequest $solicitud, AcaGrupoAsignatura $materia)
    {
        $materia->load(['grupo.carrera', 'grupo.turno', 'grupo.periodo', 'asignatura', 'docente.persona']);
        $periodo = $materia->grupo->periodo;
        $primero = $periodo->fecha_inicio->copy()->startOfMonth();
        $ultimo = $periodo->fecha_fin->copy()->startOfMonth();
        $pedido = $solicitud->validated('mes') ? Carbon::createFromFormat('Y-m-d', $solicitud->validated('mes').'-01')->startOfDay() : today()->startOfMonth();
        $mes = $pedido->max($primero)->min($ultimo);
        $anterior = $mes->copy()->subMonth()->gte($primero) ? $mes->copy()->subMonth() : null;
        $siguiente = $mes->copy()->addMonth()->lte($ultimo) ? $mes->copy()->addMonth() : null;

        $registros = TrxAsistenciaClase::with('tipo')->where('aca_grupo_asignatura_id', $materia->id)
            ->whereBetween('fecha', [$mes->toDateString(), $mes->copy()->endOfMonth()->toDateString()])->get();
        $fechas = $registros->map(fn (TrxAsistenciaClase $registro) => $registro->fecha->toDateString())->unique()->sort()->values();
        $celdas = $registros->groupBy('aca_inscripcion_id')->map(fn ($deLaInscripcion) => $deLaInscripcion->mapWithKeys(fn (TrxAsistenciaClase $registro) => [$registro->fecha->toDateString() => $registro->tipo->codigo]));
        // Totales del periodo completo por inscripción.
        $totales = TrxAsistenciaClase::resumen()->where('trx_asistencia_clase.aca_grupo_asignatura_id', $materia->id)->get()->keyBy('aca_inscripcion_id');
        // Alumnos: los que hoy están en la lista (INSCRITO y recursantes) y los que tienen registros (aunque ya no estén).
        $ids = collect(array_keys($materia->inscripcionesCalificables()))->merge($totales->keys())->unique()->values();
        $recursantes = $materia->recursamientos()->get()->map->inscripcionId()->all();
        $inscripciones = AcaInscripcion::with('alumno.persona')->whereIn('id', $ids)->get()
            ->sortBy(fn (AcaInscripcion $inscripcion) => mb_strtolower(implode(' ', [$inscripcion->alumno->persona->primer_apellido,
                $inscripcion->alumno->persona->segundo_apellido, $inscripcion->alumno->persona->nombre])))->values();
        $alumnos = Paginacion::paginarColeccion($inscripciones, $solicitud);
        $minimo = TrxAsistenciaClase::minimoPorcentaje();
        $puedePasarLista = $solicitud->user()->can('pasarLista', $materia);

        return view('academico.asistencia-materia.show', compact('materia', 'mes', 'anterior', 'siguiente', 'fechas', 'celdas', 'totales', 'alumnos', 'recursantes', 'minimo', 'puedePasarLista'));
    }
}
