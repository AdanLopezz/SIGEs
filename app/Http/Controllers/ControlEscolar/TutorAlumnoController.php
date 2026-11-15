<?php

namespace App\Http\Controllers\ControlEscolar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ControlEscolar\Concerns\RespondeCuentaDeAcceso;
use App\Http\Requests\ControlEscolar\CuentaTutorRequest;
use App\Http\Requests\ControlEscolar\DesactivarTutorRequest;
use App\Http\Requests\ControlEscolar\TutorAlumnoRequest;
use App\Models\PerAlumno;
use App\Models\PerConsentimiento;
use App\Models\PerTutorAlumno;
use App\Support\Auditoria;
use Illuminate\Validation\ValidationException;

class TutorAlumnoController extends Controller
{
    use RespondeCuentaDeAcceso;

    public function index(PerAlumno $alumno)
    {
        return $this->mostrar($alumno, new PerTutorAlumno);
    }

    public function edit(PerAlumno $alumno, PerTutorAlumno $tutor)
    {
        return $this->mostrar($alumno, $tutor);
    }

    private function mostrar(PerAlumno $alumno, PerTutorAlumno $registro)
    {
        $alumno->loadMissing('persona');
        $registro->loadMissing('persona');
        $tutores = $alumno->tutores()->with('persona.user.roles')->withExists(['consentimientos', 'confirmaciones'])
            ->orderByDesc('activo')->orderByDesc('es_contacto_emergencia')->orderBy('id')->get();
        $parentescos = PerTutorAlumno::PARENTESCOS;
        // Con un consentimiento de notificaciones vigente, la casilla queda bloqueada en su valor.
        $notificaciones = $registro->exists ? $registro->consentimientoVigente(PerConsentimiento::NOTIFICACIONES) : null;

        return view('control-escolar.alumnos.tutores', compact('alumno', 'registro', 'tutores', 'parentescos', 'notificaciones'));
    }

    public function store(TutorAlumnoRequest $solicitud, PerAlumno $alumno)
    {
        Auditoria::motivoPorDefecto('Alta de tutor del alumno');
        $tutor = PerTutorAlumno::registrar($alumno, $solicitud->validated());
        $mensaje = 'Tutor agregado correctamente.'.($tutor->personaReutilizada ? ' La persona ya estaba registrada; se conservaron sus datos.' : '');

        return redirect()->route('alumnos.tutores.index', $alumno)->with('status', $mensaje);
    }

    public function update(TutorAlumnoRequest $solicitud, PerAlumno $alumno, PerTutorAlumno $tutor)
    {
        $tutor->actualizar($solicitud->validated());

        return redirect()->route('alumnos.tutores.index', $alumno)->with('status', 'Tutor actualizado correctamente.');
    }

    public function destroy(PerAlumno $alumno, PerTutorAlumno $tutor)
    {
        try {
            $eliminado = $tutor->quitar();
        } catch (ValidationException $excepcion) {
            return back()->withErrors($excepcion->errors());
        }

        return $eliminado
            ? redirect()->route('alumnos.tutores.index', $alumno)->with('status', 'Tutor quitado correctamente.')
            : back()->withErrors(['accion' => 'No se puede quitar al tutor porque ya tiene historial (consentimientos o permisos): desactívalo.']);
    }

    public function desactivar(DesactivarTutorRequest $solicitud, PerAlumno $alumno, PerTutorAlumno $tutor)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        $tutor->desactivar();

        return redirect()->route('alumnos.tutores.index', $alumno)->with('status', 'Tutor desactivado: ya no recibe avisos ni ve al alumno en el portal.');
    }

    public function reactivar(PerAlumno $alumno, PerTutorAlumno $tutor)
    {
        Auditoria::motivoPorDefecto('Reactivación de tutor');
        $tutor->reactivar();

        return redirect()->route('alumnos.tutores.index', $alumno)->with('status', 'Tutor reactivado.');
    }

    public function crearCuenta(CuentaTutorRequest $solicitud, PerAlumno $alumno, PerTutorAlumno $tutor)
    {
        return $this->responderCuenta(fn () => $tutor->crearCuenta($solicitud->validated('email')), 'Padre de familia',
            route('alumnos.tutores.cuenta', [$alumno, $tutor]));
    }
}
