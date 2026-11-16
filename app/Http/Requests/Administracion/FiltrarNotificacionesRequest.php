<?php

namespace App\Http\Requests\Administracion;

use App\Models\ComNotificacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FiltrarNotificacionesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('usuarios.administrar') ?? false;
    }

    public function rules(): array
    {
        return ['estado' => ['nullable', Rule::in(array_keys(ComNotificacion::ESTADOS))]];
    }

    public function messages(): array
    {
        return ['estado.in' => 'Selecciona un estado válido.'];
    }
}
