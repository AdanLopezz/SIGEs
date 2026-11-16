<?php

namespace App\Http\Requests\ControlEscolar;

class DocumentacionEgresoRequest extends EgresoRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('egreso-confirmar') ?? false;
    }

    public function rules(): array
    {
        return ['certificado_entregado' => ['required', 'boolean'], 'titulado' => ['required', 'boolean'],
            'motivo' => ['required', 'string', 'max:255']];
    }
}
