<?php

namespace App\Http\Requests\Configuracion;

class PlantelRequest extends ConfiguracionRequest
{
    public function rules(): array
    {
        return [
            'clave_cct' => ['required', 'string', 'max:20', 'regex:/^[0-9]{2}[A-Z]{3}[0-9]{4}[A-Z]$/'],
            'nombre' => ['required', 'string', 'max:200'],
            'nombre_corto' => ['required', 'string', 'max:50'],
            'calificacion_minima' => ['required', 'numeric', 'between:5,10', 'decimal:0,2'],
        ];
    }
}
