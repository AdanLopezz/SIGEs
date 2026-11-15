<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Sustituye el POST de Fortify de "¿Olvidaste tu contraseña?": acepta el usuario o el correo personal
 * y el enlace siempre llega al correo personal. La respuesta es la misma exista o no la cuenta.
 */
class RecuperarContrasenaController extends Controller
{
    public function store(Request $solicitud)
    {
        $solicitud->validate(['email' => ['required', 'string', 'max:255']], [
            'email.required' => 'Escribe tu usuario o tu correo personal.',
        ], ['email' => 'usuario o correo personal']);

        User::enviarEnlacesDeRecuperacion($solicitud->input('email'));

        return back()->with('status', trans('passwords.sent'));
    }
}
