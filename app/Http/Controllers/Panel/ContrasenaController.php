<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\CambiarContrasenaRequest;
use App\Support\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// "Cambiar mi contraseña" (menú del usuario). También es la pantalla obligatoria mientras haya contraseña temporal.
class ContrasenaController extends Controller
{
    public function edit(Request $solicitud)
    {
        return view('panel.contrasena', ['obligatorio' => (bool) $solicitud->user()->debe_cambiar_password]);
    }

    public function update(CambiarContrasenaRequest $solicitud)
    {
        $usuario = $solicitud->user();
        $guardia = Auth::guard('web');
        $conRecordarme = $solicitud->hasCookie($guardia->getRecallerName());
        Auditoria::motivoPorDefecto('Cambio de contraseña por el usuario');
        $usuario->cambiarContrasena($solicitud->validated('password'), $solicitud->session()->getId());

        // Nueva sesión (la anterior se destruye) y, si usaba "Recordarme", una cookie con el token nuevo.
        if ($conRecordarme) {
            $guardia->login($usuario, true);
        } else {
            $solicitud->session()->regenerate(true);
        }

        return redirect()->route('dashboard')->with('status', 'Tu contraseña se actualizó. Se cerraron tus demás sesiones.');
    }
}
