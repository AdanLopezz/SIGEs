<?php

namespace App\Policies;

use App\Models\PerAlumno;
use App\Models\User;

// Cada acción exige su permiso y que el alumno sea visible para el usuario (PerAlumno::scopeVisiblesPara).
class AlumnoPolicy
{
    public function ver(User $usuario, PerAlumno $alumno): bool
    {
        return $usuario->can('alumnos.ver') && $alumno->esVisiblePara($usuario);
    }

    public function editar(User $usuario, PerAlumno $alumno): bool
    {
        return $usuario->can('alumnos.editar') && $alumno->esVisiblePara($usuario);
    }

    public function verSalud(User $usuario, PerAlumno $alumno): bool
    {
        return $usuario->can('alumnos.salud.ver') && $alumno->esVisiblePara($usuario);
    }
}
