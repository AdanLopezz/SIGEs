<?php

namespace App\Http\Requests\ControlEscolar;

class RegistrarEgresoRequest extends EgresoRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('egreso-confirmar') ?? false;
    }

    public function rules(): array
    {
        return ['fecha_egreso' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'promedio_general' => ['required', 'numeric', 'between:0,10', 'decimal:0,2'],
            'historial_verificado' => ['accepted'], 'motivo' => ['required', 'string', 'max:255']];
    }
}
