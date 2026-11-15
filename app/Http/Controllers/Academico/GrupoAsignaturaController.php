<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\GrupoAsignaturaRequest;
use App\Models\AcaAsignatura;
use App\Models\AcaGrupoAsignatura;
use App\Models\AcaGrupoEscolar;
use App\Models\PerPersonal;
use App\Support\PeriodoDeTrabajo;

class GrupoAsignaturaController extends Controller
{
    public function index(AcaGrupoEscolar $grupo)
    {
        return $this->mostrar($grupo, new AcaGrupoAsignatura);
    }

    public function edit(AcaGrupoEscolar $grupo, AcaGrupoAsignatura $materia)
    {
        return $this->mostrar($grupo, $materia);
    }

    private function mostrar(AcaGrupoEscolar $grupo, AcaGrupoAsignatura $registro)
    {
        $grupo->loadMissing(['carrera', 'turno', 'periodo']);
        $registro->loadMissing('asignatura');
        $materias = $grupo->materias()->with(['asignatura', 'docente.persona'])->get()->sortBy('asignatura.nombre')->values();
        $asignaturas = AcaAsignatura::activos()->delSemestre($grupo->semestre)
            ->whereNotIn('id', $materias->pluck('aca_asignatura_id'))->ordenados()->get();
        $docentes = PerPersonal::with('persona')->conRol('Docente')->ordenados()->get();
        $totalHoras = $materias->sum('asignatura.horas_semana');
        // En un periodo pasado las materias solo se consultan.
        $preparable = PeriodoDeTrabajo::permite(PeriodoDeTrabajo::PREPARAR, $grupo->periodo);
        if ($registro->exists) {
            $grupo->exigirPreparable();
        }

        return view('academico.grupos.materias', compact('grupo', 'registro', 'materias', 'asignaturas', 'docentes', 'totalHoras', 'preparable'));
    }

    public function store(GrupoAsignaturaRequest $solicitud, AcaGrupoEscolar $grupo)
    {
        AcaGrupoAsignatura::agregarA($grupo, $solicitud->validated());

        return redirect()->route('grupos.materias.index', $grupo)->with('status', 'Materia agregada correctamente.');
    }

    public function update(GrupoAsignaturaRequest $solicitud, AcaGrupoEscolar $grupo, AcaGrupoAsignatura $materia)
    {
        $materia->actualizarDocente($solicitud->validated());

        return redirect()->route('grupos.materias.index', $grupo)->with('status', 'Docente actualizado correctamente.');
    }

    public function destroy(AcaGrupoEscolar $grupo, AcaGrupoAsignatura $materia)
    {
        return $materia->eliminar()
            ? redirect()->route('grupos.materias.index', $grupo)->with('status', 'Materia quitada del grupo.')
            : back()->withErrors(['accion' => 'No se puede quitar la materia porque ya está en uso (evaluaciones, asistencia o clases en el horario; quita primero sus clases en Horarios).']);
    }
}
