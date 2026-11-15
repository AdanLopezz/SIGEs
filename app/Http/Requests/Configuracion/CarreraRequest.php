<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Validation\Rule;

class CarreraRequest extends ConfiguracionRequest
{
    public function rules(): array
    {
        return [
            'clave' => ['bail', 'required', 'string', 'max:30', Rule::unique('aca_carrera', 'clave')->ignore($this->route('carrera')?->id)],
            'nombre' => ['bail', 'required', 'string', 'max:150'],
            'tipo' => ['bail', 'required', Rule::in(['TECNICA', 'LICENCIATURA'])],
            'total_semestres' => ['bail', 'required', 'integer', 'between:1,10'],
        ];
    }
}
