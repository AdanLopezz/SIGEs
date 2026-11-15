<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Validation\Rule;

class DiaInhabilRequest extends ConfiguracionRequest
{
    public function rules(): array
    {
        return [
            'fecha' => ['bail', 'required', 'date_format:Y-m-d', Rule::unique('aca_dia_inhabil', 'fecha')->ignore($this->route('dia')?->id)],
            'motivo' => ['bail', 'required', 'string', 'max:150'],
        ];
    }
}
