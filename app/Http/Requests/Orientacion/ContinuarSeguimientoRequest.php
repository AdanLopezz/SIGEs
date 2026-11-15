<?php

namespace App\Http\Requests\Orientacion;

// "Continuar en el periodo actual": las mismas reglas que cerrar (nota final + motivo).
class ContinuarSeguimientoRequest extends CerrarSeguimientoRequest
{
    protected $errorBag = 'continuar';

    public function authorize(): bool
    {
        return $this->user()?->can('continuar', $this->route('seguimiento')) ?? false;
    }
}
