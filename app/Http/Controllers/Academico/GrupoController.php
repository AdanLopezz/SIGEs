<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\FiltrarGruposRequest;
use App\Http\Requests\Academico\GrupoRequest;
use App\Models\AcaCarrera;
use App\Models\AcaGrupoEscolar;
use App\Models\AcaInscripcion;
use App\Models\AcaPeriodoEscolar;
use App\Models\AcaTurno;
use App\Models\PerPersonal;
use App\Support\PeriodoDeTrabajo;

class GrupoController extends Controller
{
    public function index(FiltrarGruposRequest $solicitud)
    {
        $filtros = $solicitud->validated();
        $hayPeriodoActivo = AcaPeriodoEscolar::activo() !== null;
        // Sin periodo elegido se usa el de consulta (selector de la barra superior).
        $filtros['periodo'] ??= PeriodoDeTrabajo::periodoConsulta()?->id;
        $grupos = AcaGrupoEscolar::with(['carrera', 'turno', 'orientador.persona', 'tutor.persona'])
            ->withCount(['materias', 'inscripciones', 'inscripciones as alumnos_count' => fn ($consulta) => $consulta->where('estado', AcaInscripcion::INSCRITO)])
            ->delPeriodo($filtros['periodo'] ? (int) $filtros['periodo'] : null)
            ->deCarrera(isset($filtros['carrera']) ? (int) $filtros['carrera'] : null)
            ->deTurno(isset($filtros['turno']) ? (int) $filtros['turno'] : null)
            ->deSemestre(isset($filtros['semestre']) ? (int) $filtros['semestre'] : null)
            ->ordenados()->paginarListado();
        $periodos = AcaPeriodoEscolar::ordenados()->get()->mapWithKeys(fn (AcaPeriodoEscolar $periodo) => [$periodo->id => $periodo->nombre_visible.' ('.$periodo->clave.')']);
        $carreras = AcaCarrera::ordenados()->pluck('nombre', 'id');
        $turnos = AcaTurno::ordenados()->pluck('nombre', 'id');
        // Grupos, materias y docentes solo se preparan en el periodo activo y en los futuros.
        $preparable = ! $filtros['periodo'] || PeriodoDeTrabajo::permite(PeriodoDeTrabajo::PREPARAR, AcaPeriodoEscolar::find($filtros['periodo']));
        $hayPreparables = PeriodoDeTrabajo::periodosPreparables()->isNotEmpty();

        return view('academico.grupos.index', compact('grupos', 'filtros', 'periodos', 'carreras', 'turnos', 'hayPeriodoActivo', 'preparable', 'hayPreparables'));
    }

    public function create()
    {
        // Propone el periodo de consulta si se puede preparar (activo o futuro); si no, el activo.
        $consulta = PeriodoDeTrabajo::periodoConsulta();
        $periodo = PeriodoDeTrabajo::permite(PeriodoDeTrabajo::PREPARAR, $consulta) ? $consulta : PeriodoDeTrabajo::periodoActivo();

        return $this->formulario(new AcaGrupoEscolar(['aca_periodo_escolar_id' => $periodo?->id]));
    }

    public function store(GrupoRequest $solicitud)
    {
        (new AcaGrupoEscolar)->guardar($solicitud->validated());

        return redirect()->route('grupos.index')->with('status', 'Grupo creado correctamente.');
    }

    public function edit(AcaGrupoEscolar $grupo)
    {
        // Un grupo de un periodo pasado ya no se edita (se consulta en la lista y en sus materias).
        $grupo->exigirPreparable();

        return $this->formulario($grupo);
    }

    public function update(GrupoRequest $solicitud, AcaGrupoEscolar $grupo)
    {
        $grupo->guardar($solicitud->validated());

        return redirect()->route('grupos.index', ['periodo' => $grupo->aca_periodo_escolar_id])->with('status', 'Grupo actualizado correctamente.');
    }

    public function destroy(AcaGrupoEscolar $grupo)
    {
        return $grupo->eliminar()
            ? back()->with('status', 'Grupo eliminado correctamente.')
            : back()->withErrors(['accion' => 'No se puede eliminar el grupo porque ya tiene materias o alumnos.']);
    }

    private function formulario(AcaGrupoEscolar $grupo)
    {
        $periodos = PeriodoDeTrabajo::periodosPreparables()->mapWithKeys(fn (AcaPeriodoEscolar $periodo) => [$periodo->id => $periodo->nombre_visible.' ('.$periodo->clave.')']);
        // Las carreras activas, más la del grupo aunque se haya desactivado.
        $carreras = AcaCarrera::where(fn ($consulta) => $consulta->where('activo', true)
            ->when($grupo->exists, fn ($consulta) => $consulta->orWhere('id', $grupo->aca_carrera_id)))->ordenados()->get();
        $turnos = AcaTurno::ordenados()->pluck('nombre', 'id');
        $orientadores = PerPersonal::with('persona')->conRol('Orientador')->ordenados()->get();
        $tutores = PerPersonal::with('persona')->conRol('Tutor')->ordenados()->get();
        $periodoBloqueado = $grupo->tieneAlumnos();
        $semestreBloqueado = $grupo->tieneMaterias();

        return view('academico.grupos.form', compact('grupo', 'periodos', 'carreras', 'turnos', 'orientadores', 'tutores', 'periodoBloqueado', 'semestreBloqueado'));
    }
}
