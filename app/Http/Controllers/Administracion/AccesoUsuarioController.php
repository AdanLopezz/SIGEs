<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Cuentas\Concerns\MuestraDatosDeAcceso;
use App\Http\Requests\Cuentas\NuevaContrasenaRequest;
use App\Models\User;

// Datos de acceso de cualquier cuenta (usuarios.administrar), salvo la del usuario Sistema.
class AccesoUsuarioController extends Controller
{
    use MuestraDatosDeAcceso;

    public function show(User $usuario)
    {
        return $this->vistaDatosDeAcceso($usuario, $this->rutas($usuario));
    }

    public function imprimir(User $usuario)
    {
        return $this->vistaDatosDeAcceso($usuario, $this->rutas($usuario), imprimir: true);
    }

    public function nuevaContrasena(NuevaContrasenaRequest $solicitud, User $usuario)
    {
        abort_if($usuario->esSistema(), 404);

        return $this->responderNuevaContrasena($solicitud, $usuario, route('usuarios.acceso', $usuario));
    }

    private function rutas(User $usuario): array
    {
        return [
            'volver' => [route('usuarios.index'), 'Volver a Usuarios'],
            'ver' => route('usuarios.acceso', $usuario),
            'imprimir' => route('usuarios.acceso.imprimir', $usuario),
            'nueva' => route('usuarios.acceso.nueva-contrasena', $usuario),
        ];
    }
}
