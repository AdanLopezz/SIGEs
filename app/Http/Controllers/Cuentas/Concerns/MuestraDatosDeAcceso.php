<?php

namespace App\Http\Controllers\Cuentas\Concerns;

use App\Http\Requests\Cuentas\NuevaContrasenaRequest;
use App\Models\SysConfiguracion;
use App\Models\User;
use App\Support\Auditoria;
use Illuminate\Validation\ValidationException;

/**
 * "Datos de acceso" de una cuenta (Usuarios y ficha del alumno): usuario y contraseña temporal mientras exista.
 * $rutas = ['volver' => [url, texto], 'ver' => url, 'imprimir' => url, 'nueva' => url].
 * $permitido = false muestra solo el aviso (p. ej. la cuenta de un padre que también es personal, vista desde la ficha).
 */
trait MuestraDatosDeAcceso
{
    protected function vistaDatosDeAcceso(User $usuario, array $rutas, bool $permitido = true, bool $imprimir = false)
    {
        abort_if($usuario->esSistema(), 404);
        $usuario->loadMissing(['persona', 'roles']);
        $temporal = $permitido ? $usuario->datosDeAcceso() : ['valor' => null, 'ilegible' => false];
        if ($imprimir && $temporal['valor'] === null) {
            return redirect($rutas['ver']);
        }
        // Cada vez que se muestra la contraseña queda en la bitácora (sin el valor).
        if ($temporal['valor'] !== null) {
            $usuario->registrarConsultaDeAcceso($imprimir);
        }
        $puedeGenerar = $permitido && ! $usuario->is(auth()->user());

        return response()
            ->view($imprimir ? 'cuentas.datos-acceso-imprimir' : 'cuentas.datos-acceso', compact('usuario', 'temporal', 'rutas', 'permitido', 'puedeGenerar') + ['plantel' => SysConfiguracion::actual()])
            ->header('Cache-Control', 'no-store, private');
    }

    protected function responderNuevaContrasena(NuevaContrasenaRequest $solicitud, User $usuario, string $ver)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        try {
            $usuario->generarNuevaContrasena();
        } catch (ValidationException $excepcion) {
            return back()->withErrors($excepcion->errors());
        }

        return redirect($ver)->with('status', 'Se generó una nueva contraseña temporal y se cerraron las sesiones de la cuenta. Entrégala a la persona; deberá cambiarla al entrar.');
    }
}
