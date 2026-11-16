<?php

namespace App\Policies;

use App\Models\User;

class EgresoPolicy
{
    public function confirmar(User $usuario): bool
    {
        return $usuario->can('egreso.administrar') && $usuario->hasAnyRole(['Administrador', 'Control Escolar']);
    }
}
