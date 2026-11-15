<?php

namespace App\Policies;

use App\Models\TrxSeguimientoAlumno;
use App\Models\User;

/**
 * Cada acción exige seguimiento.administrar y que el caso sea visible (TrxSeguimientoAlumno::scopeVisiblesPara).
 * Los casos de periodos anteriores son de solo lectura: solo se cierran o se continúan en el periodo actual.
 */
class SeguimientoPolicy
{
    public function ver(User $usuario, TrxSeguimientoAlumno $caso): bool
    {
        return $usuario->can('seguimiento.administrar') && $caso->esVisiblePara($usuario);
    }

    // Agregar notas y cambiar el riesgo de abandono.
    public function operar(User $usuario, TrxSeguimientoAlumno $caso): bool
    {
        return ! $caso->estaCerrado() && $caso->esDelPeriodoActivo() && $this->ver($usuario, $caso);
    }

    public function cerrar(User $usuario, TrxSeguimientoAlumno $caso): bool
    {
        return ! $caso->estaCerrado() && $this->ver($usuario, $caso);
    }

    public function reabrir(User $usuario, TrxSeguimientoAlumno $caso): bool
    {
        return $caso->estaCerrado() && $caso->esDelPeriodoActivo() && $this->ver($usuario, $caso);
    }

    // Caso abierto de un periodo pasado, con el alumno inscrito hoy en un grupo del usuario.
    public function continuar(User $usuario, TrxSeguimientoAlumno $caso): bool
    {
        return ! $caso->estaCerrado() && $caso->esDePeriodoPasado() && $this->ver($usuario, $caso)
            && TrxSeguimientoAlumno::alumnosElegibles($usuario)->whereKey($caso->inscripcion->per_alumno_id)->exists();
    }
}
