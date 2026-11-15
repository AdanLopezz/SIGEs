<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un usuario sin ningún rol no tiene acceso al sistema:
 * así se "desactiva" una cuenta sin borrarla (ver CRUD de usuarios).
 */
class RequiereRol
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->roles()->doesntExist()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Tu cuenta no tiene acceso al sistema. Comunícate con el administrador.'], 'login');
        }

        return $next($request);
    }
}
