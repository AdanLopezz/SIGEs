<?php

namespace App\Http\Controllers\Cuentas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cuentas\GenerarUsuarioRequest;
use App\Support\CredencialesDeAcceso;

// Responde con un usuario libre para el botón "Regenerar" (JSON). Al guardar, el servidor lo vuelve a comprobar.
class GenerarUsuarioController extends Controller
{
    public function __invoke(GenerarUsuarioRequest $solicitud)
    {
        return response()->json([
            'usuario' => CredencialesDeAcceso::usuario($solicitud->validated('nombre'), $solicitud->validated('primer_apellido')),
        ]);
    }
}
