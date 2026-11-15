<?php

namespace App\Http\Requests\Orientacion;

// Nota de un caso no cerrado del periodo activo. La fecha (no futura, no antes de la apertura) la valida el modelo.
class NotaSeguimientoRequest extends SeguimientoRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('operar', $this->route('seguimiento')) ?? false;
    }

    public function rules(): array
    {
        return ['fecha' => ['bail', 'required', 'date_format:Y-m-d']] + $this->reglasDeNota();
    }
}
