<?php

namespace App\Http\Requests\Vinculacion;

class FiltrarEmpresasRequest extends VinculacionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['dual.administrar', 'dual.revisar']) ?? false;
    }

    public function rules(): array
    {
        return ['buscar' => ['nullable', 'string', 'max:100']];
    }
}
