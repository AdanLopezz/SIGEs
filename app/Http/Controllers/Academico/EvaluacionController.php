<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\CerrarEvaluacionRequest;
use App\Http\Requests\Academico\FiltrarMateriasRequest;
use App\Http\Requests\Academico\ProgramarEvaluacionesRequest;
use App\Models\AcaEvaluacion;
use App\Models\AcaGrupoAsignatura;
use App\Models\AcaGrupoEscolar;
use App\Models\CatTipoEvaluacion;
use App\Support\Auditoria;

class EvaluacionController extends Controller
{
    public function index(FiltrarMateriasRequest $solicitud)
    {
        $usuario = $solicitud->user();
        $filtros = $solicitud->validated();
        $grupos = AcaGrupoEscolar::with(['carrera', 'turno'])
            ->whereIn('id', AcaGrupoAsignatura::visiblesParaCalificar($usuario)->select('aca_grupo_escolar_id'))->ordenados()->get();
        $materias = AcaGrupoAsignatura::with(['grupo.carrera', 'grupo.turno', 'asignatura', 'docente.persona', 'evaluaciones.tipo'])
            ->visiblesParaCalificar($usuario)->deGrupo(isset($filtros['grupo']) ? (int) $filtros['grupo'] : null)
            ->ordenados()->paginarListado();
        $tipos = CatTipoEvaluacion::pluck('nombre', 'id');
        $esquemaEstandar = collect(AcaEvaluacion::pesosEstandar())->map(fn ($peso, $tipo) => $tipos[$tipo].' '.$peso.' %')->implode(', ');

        return view('academico.evaluaciones.index', compact('materias', 'grupos', 'filtros', 'esquemaEstandar'));
    }

    public function edit(AcaGrupoAsignatura $materia)
    {
        $materia->load(['grupo.carrera', 'grupo.turno', 'asignatura', 'docente.persona']);
        $tipos = CatTipoEvaluacion::ordenados()->get();
        $evaluaciones = $materia->evaluaciones()->with('tipo')->withCount('calificaciones')->get()->keyBy('cat_tipo_evaluacion_id');
        $esquemaEstandar = AcaEvaluacion::pesosEstandar();

        return view('academico.evaluaciones.form', compact('materia', 'tipos', 'evaluaciones', 'esquemaEstandar'));
    }

    public function update(ProgramarEvaluacionesRequest $solicitud, AcaGrupoAsignatura $materia)
    {
        Auditoria::motivoPorDefecto('Programación de evaluaciones');
        AcaEvaluacion::programar($materia, $solicitud->validated('pesos'));

        return redirect()->route('evaluaciones.edit', $materia)->with('status', 'Evaluaciones programadas correctamente.');
    }

    public function estandar(AcaGrupoAsignatura $materia)
    {
        Auditoria::motivoPorDefecto('Esquema estándar de evaluaciones');
        AcaEvaluacion::aplicarEsquemaEstandar($materia);

        return back()->with('status', 'Se aplicó el esquema estándar a '.$materia->load(['asignatura', 'grupo'])->nombre.'.');
    }

    public function cerrar(CerrarEvaluacionRequest $solicitud, AcaEvaluacion $evaluacion)
    {
        Auditoria::motivoPorDefecto('Cierre de evaluación');
        $evaluacion->cerrar();

        return back()->with('status', $evaluacion->tipo->nombre.' cerrada. Desde ahora solo Control Escolar corrige sus calificaciones.');
    }
}
