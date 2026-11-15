<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class CambiarContrasenaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['bail', 'required', 'string', 'current_password:web'],
            'password' => ['bail', 'required', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validador) {
                if (! $validador->errors()->has('password') && is_string($this->input('password'))
                    && Hash::check($this->input('password'), $this->user()->password)) {
                    $validador->errors()->add('password', 'La nueva contraseña debe ser distinta de la actual.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return ['current_password' => 'contraseña actual', 'password' => 'nueva contraseña'];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.confirmed' => 'La confirmación no coincide con la nueva contraseña.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.letters' => 'La nueva contraseña debe incluir letras.',
            'password.mixed' => 'La nueva contraseña debe incluir mayúsculas y minúsculas.',
            'password.numbers' => 'La nueva contraseña debe incluir al menos un número.',
        ];
    }
}
