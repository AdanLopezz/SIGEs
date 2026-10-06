<?php

namespace App\Http\Requests\Auth;

use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

class LoginRequest extends FortifyLoginRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Escribe tu correo electrónico.',
            'email.email' => 'Escribe un correo electrónico válido.',
            'email.string' => 'El correo debe ser texto.',
            'email.max' => 'El correo no puede superar 255 caracteres.',
            'password.required' => 'Escribe tu contraseña.',
            'password.string' => 'La contraseña debe ser texto.',
            'remember.boolean' => 'La opción Recordarme no es válida.',
        ];
    }
}
