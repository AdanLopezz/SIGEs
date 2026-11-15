<?php

namespace App\Http\Controllers\Orientacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orientacion\FiltrarAsistenciaClaseRequest;
use App\Models\AcaAsignatura;
use App\Models\AcaGrupoAsignatura;
use App\Models\AcaGrupoEscolar;
use App\Models\TrxAsistenciaClase;
use App\Support\PeriodoDeTrabajo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Asistencia por clase de los alumnos de mis grupos (asistencia.ver): una fila por alumno y materia con clases, faltas,
 * retardos, justificadas y porcentaje, en el periodo de consulta. Grupos con AcaGrupoEscolar::visiblesEnAsistencia().
 */
class AsistenciaClaseController extends Controller
{
    public function index(FiltrarAsistenciaClaseRequest $solicitud)
    {
        $usuario = $solicitud->user();
        $filtros = $solicitud->validated();
        $grupos = AcaGrupoEscolar::with(['carrera', 'turno'])->visiblesEnAsistencia($usuario, PeriodoDeTrabajo::idConsulta())->ordenados()->get();
        $grupoIds = isset($filtros['grupo']) ? $grupos->pluck('id')->intersect([(int) $filtros['grupo']])->values()->all() : $grupos->pluck('id')->all();
        $asignaturas = AcaAsignatura::whereIn('id', AcaGrupoAsignatura::whereIn('aca_grupo_escolar_id', $grupoIds)->select('aca_asignatura_id'))->ordenados()->get();
        $nombre = DB::raw("(select concat_ws(' ', per_persona.primer_apellido, per_persona.segundo_apellido, per_persona.nombre)
            from aca_inscripcion join per_alumno on per_alumno.id = aca_inscripcion.per_alumno_id join per_persona on per_persona.id = per_alumno.per_persona_id
            where aca_inscripcion.id = trx_asistencia_clase.aca_inscripcion_id)");
        $filas = TrxAsistenciaClase::resumen()
            ->whereHas('inscripcion', fn (Builder $inscripciones) => $inscripciones->whereIn('aca_grupo_escolar_id', $grupoIds))
            ->whereHas('materia', fn (Builder $materias) => $materias->whereIn('aca_grupo_escolar_id', $grupoIds)
                ->when(isset($filtros['asignatura']), fn (Builder $materias) => $materias->where('aca_asignatura_id', (int) $filtros['asignatura'])))
            ->when(! empty($filtros['bajo_minimo']), fn (Builder $consulta) => $consulta->bajoMinimo())
            ->with(['inscripcion.alumno.persona', 'inscripcion.grupo', 'materia.asignatura', 'materia.docente.persona'])
            ->orderBy($nombre)->orderBy('trx_asistencia_clase.aca_inscripcion_id')->orderBy('trx_asistencia_clase.aca_grupo_asignatura_id')
            ->paginarListado();
        $minimo = TrxAsistenciaClase::minimoPorcentaje();

        return view('orientacion.asistencia-clase.index', compact('filas', 'grupos', 'asignaturas', 'filtros', 'minimo'));
    }
}
