<?php

namespace App\Http\Controllers\ControlEscolar\Concerns;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

trait RespondeCuentaDeAcceso
{
    // Ejecuta la creación de la cuenta: una cuenta nueva lleva a "Datos de acceso" ($datos = su URL); si ya existía, mensaje en la ficha.
    private function responderCuenta(callable $crear, string $rol, string $datos): RedirectResponse
    {
        try {
            /** @var User $usuario */
            $usuario = $crear();
        } catch (ValidationException $excepcion) {
            return back()->withErrors($excepcion->errors());
        }

        return match ($usuario->resultado) {
            User::CUENTA_CREADA => redirect($datos)->with('status', "Cuenta creada con el rol {$rol}. Entrega estos datos de acceso a la persona."),
            User::ROL_AGREGADO => back()->with('status', "La persona ya tenía cuenta ({$usuario->email}); se le agregó el rol {$rol}. Entra con su usuario y contraseña actuales."),
            default => back()->with('status', "La persona ya tenía cuenta con el rol {$rol}."),
        };
    }
}
