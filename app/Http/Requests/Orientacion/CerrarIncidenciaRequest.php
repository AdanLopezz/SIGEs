<?php

namespace App\Http\Requests\Orientacion;

class CerrarIncidenciaRequest extends OrientacionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('cerrar', $this->route('incidencia')) ?? false;
    }

    public function rules(): array
    {
        return ['resolucion' => ['bail', 'required', 'string', 'min:10', 'max:2000']];
    }
}
