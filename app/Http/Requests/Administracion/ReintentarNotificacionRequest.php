<?php

namespace App\Http\Requests\Administracion;

use Illuminate\Foundation\Http\FormRequest;

class ReintentarNotificacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('usuarios.administrar') ?? false;
    }

    public function rules(): array
    {
        return ['motivo' => ['required', 'string', 'max:255'], 'entrega_descartada' => ['sometimes', 'boolean']];
    }

    public function messages(): array
    {
        return ['motivo.required' => 'Explica por qué se reintentará el aviso.',
            'motivo.max' => 'El motivo no debe superar 255 caracteres.',
            'entrega_descartada.boolean' => 'La confirmación de entrega no es válida.'];
    }
}
