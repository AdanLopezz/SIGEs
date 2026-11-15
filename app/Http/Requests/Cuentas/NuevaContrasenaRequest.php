<?php

namespace App\Http\Requests\Cuentas;

use Illuminate\Foundation\Http\FormRequest;

// "Generar nueva contraseña": solo el motivo (queda en la bitácora). Los permisos los ponen las rutas.
class NuevaContrasenaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['motivo' => ['required', 'string', 'max:255']];
    }

    public function attributes(): array
    {
        return ['motivo' => 'motivo'];
    }
}
