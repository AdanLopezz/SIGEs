<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function create(array $input): User
    {
        if (is_string($input['email'] ?? null)) {
            $input['email'] = Str::lower(trim($input['email']));
        }

        $validated = Validator::make($input, [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols(), function ($attribute, $value, $fail) {
                // Bcrypt uses at most 72 bytes, including multibyte passwords.
                if (is_string($value) && strlen($value) > 72) {
                    $fail('La contraseña no puede superar 72 bytes.');
                }
            }],
        ], [
            'name.required' => 'Escribe tu nombre completo.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede superar 100 caracteres.',
            'email.required' => 'Escribe tu correo electrónico.',
            'email.string' => 'El correo debe ser texto.',
            'email.email' => 'Escribe un correo electrónico válido.',
            'email.max' => 'El correo no puede superar 255 caracteres.',
            'email.unique' => 'No es posible registrar este correo electrónico.',
            'password.required' => 'Escribe una contraseña.',
            'password.string' => 'La contraseña debe ser texto.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password.min' => 'Usa al menos 12 caracteres en la contraseña.',
            'password.mixed' => 'Incluye mayúsculas y minúsculas en la contraseña.',
            'password.numbers' => 'Incluye al menos un número en la contraseña.',
            'password.symbols' => 'Incluye al menos un símbolo en la contraseña.',
        ])->validate();

        // User's hashed cast is the single hashing boundary.
        return User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);
    }
}
