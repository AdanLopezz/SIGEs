<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mientras la cuenta tenga una contraseña temporal (debe_cambiar_password), solo puede cambiarla o salir.
 * Las rutas de "Cambiar mi contraseña" y "logout" quedan fuera de este middleware (routes/web.php).
 */
class DebeCambiarContrasena
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->debe_cambiar_password) {
            abort_if($request->expectsJson(), 403, 'Debes cambiar tu contraseña temporal antes de continuar.');

            return redirect()->route('mi-cuenta.contrasena.edit');
        }

        return $next($request);
    }
}
