<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Validation\Rule;

class TurnoRequest extends ConfiguracionRequest
{
    public function rules(): array
    {
        return [
            'nombre' => ['bail', 'required', 'string', 'max:20', Rule::unique('aca_turno', 'nombre')->ignore($this->route('turno')?->id)],
            'hora_entrada' => ['bail', 'required', 'date_format:H:i'],
            'hora_salida' => ['bail', 'required', 'date_format:H:i', 'after:hora_entrada'],
            'tolerancia_minutos' => ['bail', 'required', 'integer', 'between:0,60'],
            'limite_retardo_min' => ['bail', 'required', 'integer', 'between:0,120', 'gte:tolerancia_minutos'],
        ];
    }
}
