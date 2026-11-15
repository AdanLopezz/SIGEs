<?php

namespace App\Http\Controllers\ControlEscolar;

use App\Http\Controllers\Controller;
use App\Http\Requests\ControlEscolar\InscripcionRequest;
use App\Models\AcaGrupoEscolar;
use App\Models\AcaPeriodoEscolar;
use App\Models\PerAlumno;
use App\Support\Auditoria;

class InscripcionController extends Controller
{
    public function edit(PerAlumno $alumno)
    {
        $alumno->load(['persona', 'carrera', 'estado', 'inscripcionActual.grupo.carrera', 'inscripcionActual.grupo.turno']);
        $actual = $alumno->inscripcionActual;
        $grupos = AcaGrupoEscolar::with(['carrera', 'turno'])->delPeriodoActivo()->deCarrera($alumno->aca_carrera_id)
            ->when($actual, fn ($consulta) => $consulta->whereKeyNot($actual->aca_grupo_escolar_id))->ordenados()->get();
        $periodoActivo = AcaPeriodoEscolar::activo();

        return view('control-escolar.alumnos.inscripcion', compact('alumno', 'actual', 'grupos', 'periodoActivo'));
    }

    public function inscribir(InscripcionRequest $solicitud, PerAlumno $alumno)
    {
        Auditoria::motivoPorDefecto('Inscripción de alumno');
        $alumno->inscribir(AcaGrupoEscolar::findOrFail($solicitud->validated('aca_grupo_escolar_id')));

        return redirect()->route('alumnos.show', $alumno)->with('status', 'Alumno inscrito correctamente.');
    }

    public function cambiarGrupo(InscripcionRequest $solicitud, PerAlumno $alumno)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        $alumno->cambiarGrupo(AcaGrupoEscolar::findOrFail($solicitud->validated('aca_grupo_escolar_id')));

        return redirect()->route('alumnos.show', $alumno)->with('status', 'Cambio de grupo registrado correctamente.');
    }
}
