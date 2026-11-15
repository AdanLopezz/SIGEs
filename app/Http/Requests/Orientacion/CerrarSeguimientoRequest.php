<?php

namespace App\Http\Requests\Orientacion;

// Nota final (tipo, descripción y acuerdos) + motivo para la bitácora.
class CerrarSeguimientoRequest extends SeguimientoRequest
{
    protected $errorBag = 'cerrar';

    public function authorize(): bool
    {
        return $this->user()?->can('cerrar', $this->route('seguimiento')) ?? false;
    }

    public function rules(): array
    {
        return $this->reglasDeNota() + ['motivo' => ['bail', 'required', 'string', 'max:255']];
    }
}
