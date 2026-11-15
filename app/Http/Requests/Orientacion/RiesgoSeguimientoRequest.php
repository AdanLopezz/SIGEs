<?php

namespace App\Http\Requests\Orientacion;

class RiesgoSeguimientoRequest extends SeguimientoRequest
{
    protected $errorBag = 'riesgo';

    public function authorize(): bool
    {
        return $this->user()?->can('operar', $this->route('seguimiento')) ?? false;
    }

    public function rules(): array
    {
        return ['riesgo_abandono' => ['bail', 'required', 'boolean'], 'motivo' => ['bail', 'required', 'string', 'max:255']];
    }
}
