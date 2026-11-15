<?php

namespace App\Policies;

use App\Models\TrxIncidencia;
use App\Models\User;
use App\Support\PeriodoDeTrabajo;

// Cada acción exige su permiso y que la incidencia sea visible (TrxIncidencia::scopeVisiblesPara).
class IncidenciaPolicy
{
    public function ver(User $usuario, TrxIncidencia $incidencia): bool
    {
        return $usuario->canAny(['incidencias.registrar', 'incidencias.cerrar']) && $incidencia->esVisiblePara($usuario);
    }

    // Solo abierta, y por quien la reportó o por quien puede cerrarla.
    public function editar(User $usuario, TrxIncidencia $incidencia): bool
    {
        // Solo las del periodo activo (tomar seguimiento y cerrar sí se permiten en cualquier periodo).
        return $incidencia->estaAbierta()
            && PeriodoDeTrabajo::permite(PeriodoDeTrabajo::OPERAR, $incidencia->inscripcion?->grupo?->periodo)
            && ($incidencia->reportado_por_user_id === $usuario->id || $usuario->can('incidencias.cerrar'))
            && $this->ver($usuario, $incidencia);
    }

    public function seguimiento(User $usuario, TrxIncidencia $incidencia): bool
    {
        return $incidencia->estaAbierta() && $usuario->can('incidencias.cerrar') && $incidencia->esVisiblePara($usuario);
    }

    public function cerrar(User $usuario, TrxIncidencia $incidencia): bool
    {
        return $incidencia->estado !== TrxIncidencia::CERRADA && $usuario->can('incidencias.cerrar') && $incidencia->esVisiblePara($usuario);
    }
}
