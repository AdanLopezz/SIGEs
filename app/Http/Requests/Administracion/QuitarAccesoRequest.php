<?php

namespace App\Http\Requests\Administracion;

use Illuminate\Foundation\Http\FormRequest;

class QuitarAccesoRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if($this->route('usuario')?->esSistema(), 404);

        return $this->user()?->can('usuarios.administrar') ?? false;
    }

    public function rules(): array
    {
        return ['motivo' => ['required', 'string', 'max:255']];
    }

    public function attributes(): array
    {
        return ['motivo' => 'motivo del cambio'];
    }

    public function messages(): array
    {
        return ['required' => 'El campo :attribute es obligatorio.', 'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no debe superar :max caracteres.'];
    }
}
