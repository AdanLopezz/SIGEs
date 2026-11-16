<?php

namespace App\Http\Requests\ControlEscolar;

use Illuminate\Validation\Rule;

class FiltrarEgresoRequest extends EgresoRequest
{
    public function rules(): array
    {
        return ['buscar' => ['nullable', 'string', 'max:100'], 'carrera' => ['nullable', 'integer', 'exists:aca_carrera,id'],
            'periodo' => ['nullable', 'integer', 'exists:aca_periodo_escolar,id'],
            'grupo' => ['nullable', 'integer', 'exists:aca_grupo_escolar,id'],
            'lista' => ['nullable', Rule::in(['candidatos', 'egresados'])]];
    }
}
