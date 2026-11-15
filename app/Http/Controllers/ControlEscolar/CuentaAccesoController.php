<?php

namespace App\Http\Controllers\ControlEscolar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Cuentas\Concerns\MuestraDatosDeAcceso;
use App\Http\Requests\Cuentas\NuevaContrasenaRequest;
use App\Models\PerAlumno;
use App\Models\PerPersona;
use App\Models\PerTutorAlumno;
use App\Models\User;

/**
 * Datos de acceso de la cuenta del alumno y de sus tutores, desde la ficha (alumnos.editar + AlumnoPolicy).
 * Solo para cuentas que son únicamente de Alumno o Padre de familia: si la persona también es personal
 * (p. ej. un docente que es padre), su cuenta se administra en Usuarios.
 */
class CuentaAccesoController extends Controller
{
    use MuestraDatosDeAcceso;

    public function alumno(PerAlumno $alumno)
    {
        $usuario = $this->cuentaDe($alumno->persona);

        return $this->vistaDatosDeAcceso($usuario, $this->rutasAlumno($alumno), $usuario->esSoloDeAlumnoOPadre());
    }

    public function imprimirAlumno(PerAlumno $alumno)
    {
        $usuario = $this->cuentaDe($alumno->persona);

        return $this->vistaDatosDeAcceso($usuario, $this->rutasAlumno($alumno), $usuario->esSoloDeAlumnoOPadre(), true);
    }

    public function nuevaAlumno(NuevaContrasenaRequest $solicitud, PerAlumno $alumno)
    {
        $usuario = $this->cuentaAdministrable($alumno->persona);

        return $this->responderNuevaContrasena($solicitud, $usuario, route('alumnos.cuenta', $alumno));
    }

    public function tutor(PerAlumno $alumno, PerTutorAlumno $tutor)
    {
        $usuario = $this->cuentaDe($tutor->persona);

        return $this->vistaDatosDeAcceso($usuario, $this->rutasTutor($alumno, $tutor), $usuario->esSoloDeAlumnoOPadre());
    }

    public function imprimirTutor(PerAlumno $alumno, PerTutorAlumno $tutor)
    {
        $usuario = $this->cuentaDe($tutor->persona);

        return $this->vistaDatosDeAcceso($usuario, $this->rutasTutor($alumno, $tutor), $usuario->esSoloDeAlumnoOPadre(), true);
    }

    public function nuevaTutor(NuevaContrasenaRequest $solicitud, PerAlumno $alumno, PerTutorAlumno $tutor)
    {
        $usuario = $this->cuentaAdministrable($tutor->persona);

        return $this->responderNuevaContrasena($solicitud, $usuario, route('alumnos.tutores.cuenta', [$alumno, $tutor]));
    }

    private function cuentaDe(PerPersona $persona): User
    {
        $usuario = $persona->user;
        abort_unless($usuario, 404);

        return $usuario;
    }

    private function cuentaAdministrable(PerPersona $persona): User
    {
        $usuario = $this->cuentaDe($persona);
        abort_unless($usuario->esSoloDeAlumnoOPadre(), 403);

        return $usuario;
    }

    private function rutasAlumno(PerAlumno $alumno): array
    {
        return [
            'volver' => [route('alumnos.show', $alumno), 'Volver a la ficha'],
            'ver' => route('alumnos.cuenta', $alumno),
            'imprimir' => route('alumnos.cuenta.imprimir', $alumno),
            'nueva' => route('alumnos.cuenta.nueva-contrasena', $alumno),
        ];
    }

    private function rutasTutor(PerAlumno $alumno, PerTutorAlumno $tutor): array
    {
        return [
            'volver' => [route('alumnos.show', $alumno), 'Volver a la ficha'],
            'ver' => route('alumnos.tutores.cuenta', [$alumno, $tutor]),
            'imprimir' => route('alumnos.tutores.cuenta.imprimir', [$alumno, $tutor]),
            'nueva' => route('alumnos.tutores.cuenta.nueva-contrasena', [$alumno, $tutor]),
        ];
    }
}
