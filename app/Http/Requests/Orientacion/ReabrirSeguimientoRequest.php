<?php

namespace App\Http\Requests\Orientacion;

class ReabrirSeguimientoRequest extends SeguimientoRequest
{
    protected $errorBag = 'reabrir';

    public function authorize(): bool
    {
        return $this->user()?->can('reabrir', $this->route('seguimiento')) ?? false;
    }

    public function rules(): array
    {
        return ['motivo' => ['bail', 'required', 'string', 'max:255']];
    }
}
