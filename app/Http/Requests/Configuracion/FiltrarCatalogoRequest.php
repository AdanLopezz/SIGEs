<?php

namespace App\Http\Requests\Configuracion;

class FiltrarCatalogoRequest extends ConfiguracionRequest
{
    public function rules(): array
    {
        return ['buscar' => ['nullable', 'string', 'max:100'], 'semestre' => ['nullable', 'integer', 'between:1,10']];
    }
}
