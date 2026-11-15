<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Validation\Rule;

class EspacioRequest extends ConfiguracionRequest
{
    public function rules(): array
    {
        return [
            'nombre' => ['bail', 'required', 'string', 'max:60', Rule::unique('org_espacio_fisico', 'nombre')->ignore($this->route('espacio')?->id)],
            'tipo' => ['bail', 'required', Rule::in(['AULA', 'LABORATORIO', 'TALLER', 'AUDITORIO', 'CANCHA'])],
            'capacidad' => ['bail', 'required', 'integer', 'between:1,500'],
        ];
    }
}
