<?php

namespace App\Http\Requests\Vinculacion;

class CancelarConvenioRequest extends VinculacionRequest
{
    protected $errorBag = 'cancelar';

    public function authorize(): bool
    {
        return $this->user()?->can('dual.administrar') ?? false;
    }

    public function rules(): array
    {
        return ['motivo' => ['bail', 'required', 'string', 'max:255']];
    }
}
