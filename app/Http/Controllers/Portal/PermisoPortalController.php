<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ConfirmarPermisoPortalRequest;
use App\Models\TrxPermisoConfirmacion;
use App\Models\TrxPermisoSalida;

// Solo el padre: autorizar o rechazar un permiso de salida pendiente de su hijo.
class PermisoPortalController extends Controller
{
    public function confirmar(ConfirmarPermisoPortalRequest $solicitud, TrxPermisoSalida $permiso)
    {
        $decision = $solicitud->validated('decision');
        $confirmacion = $permiso->confirmarDesdePortal($solicitud->user(), $decision);

        return redirect()->route('mis-hijos.permisos.index')->with('status', 'Permiso '.($decision === TrxPermisoConfirmacion::AUTORIZA ? 'autorizado' : 'rechazado')
            .'. '.$confirmacion->evidencia.'.');
    }
}
