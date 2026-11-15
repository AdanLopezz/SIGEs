<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ], [], ['password' => 'contraseña'])->validate();

        // Igual que «Cambiar mi contraseña»: borra la temporal, quita el cambio obligatorio y cierra sus sesiones.
        $user->cambiarContrasena($input['password']);
    }
}
