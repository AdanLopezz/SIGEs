<?php

namespace App\Http\Requests\Vinculacion;

use App\Models\OrgConvenio;
use Illuminate\Validation\Rule;

class FiltrarConveniosRequest extends VinculacionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['dual.administrar', 'dual.revisar']) ?? false;
    }

    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:100'],
            'tipo' => ['nullable', Rule::in(array_keys(OrgConvenio::TIPOS))],
            'estado' => ['nullable', Rule::in(array_keys(OrgConvenio::ESTADOS))],
            'empresa' => ['nullable', 'integer'],
            'vence_pronto' => ['nullable', Rule::in(['1'])],
        ];
    }
}
