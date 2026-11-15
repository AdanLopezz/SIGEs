<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Validation\Rule;

class AreaRequest extends ConfiguracionRequest
{
    public function rules(): array
    {
        return [
            'nombre' => ['bail', 'required', 'string', 'max:150', Rule::unique('org_area', 'nombre')->ignore($this->route('area')?->id)],
        ];
    }
}
