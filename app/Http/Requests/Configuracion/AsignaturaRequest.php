<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Validation\Rule;

class AsignaturaRequest extends ConfiguracionRequest
{
    public function rules(): array
    {
        return [
            'clave' => ['bail', 'required', 'string', 'max:30', Rule::unique('aca_asignatura', 'clave')->ignore($this->route('asignatura')?->id)],
            'nombre' => ['bail', 'required', 'string', 'max:150'],
            'semestre' => ['bail', 'required', 'integer', 'between:1,10'],
            'horas_semana' => ['bail', 'required', 'integer', 'between:1,40'],
        ];
    }
}
