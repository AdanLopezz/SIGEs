<?php

namespace App\Policies;

use App\Models\ComNotificacion;
use App\Models\PerAlumno;
use App\Models\TrxPermisoSalida;
use App\Models\User;

/**
 * Portal de Alumno y Padre de familia (sin permisos de Spatie: el acceso es por rol y por la relación con el alumno).
 * Se registra con Gate::define en AppServiceProvider, porque PerAlumno ya usa AlumnoPolicy.
 * Los consentimientos no cambian lo que se ve.
 */
class PortalPolicy
{
    public const ALUMNO = 'Alumno';

    public const PADRE = 'Padre de familia';

    public const ROLES = [self::ALUMNO, self::PADRE];

    // "Mi escuela": el alumno de la persona de la cuenta.
    public function propio(User $usuario, PerAlumno $alumno): bool
    {
        return $usuario->hasRole(self::ALUMNO) && $usuario->per_persona_id !== null && $alumno->per_persona_id === $usuario->per_persona_id;
    }

    // "Mis hijos": un alumno del que la persona de la cuenta es tutor ACTIVO.
    public function hijo(User $usuario, PerAlumno $alumno): bool
    {
        return $usuario->hasRole(self::PADRE) && $usuario->per_persona_id !== null
            && $alumno->tutores()->activos()->where('per_persona_id', $usuario->per_persona_id)->exists();
    }

    // Solo el padre, sobre un permiso de su hijo, pendiente y con la salida aún por venir.
    public function confirmarPermiso(User $usuario, TrxPermisoSalida $permiso): bool
    {
        return $permiso->puedeConfirmarse() && $this->hijo($usuario, $permiso->inscripcion->alumno);
    }

    public function verAviso(User $usuario, ComNotificacion $aviso): bool
    {
        return $usuario->hasAnyRole(self::ROLES) && $usuario->per_persona_id !== null && $aviso->per_persona_id === $usuario->per_persona_id;
    }
}
