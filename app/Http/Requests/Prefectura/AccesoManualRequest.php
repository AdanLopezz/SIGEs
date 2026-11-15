<?php

namespace App\Http\Requests\Prefectura;

class AccesoManualRequest extends PrefecturaRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('acceso.registrar') ?? false;
    }

    public function rules(): array
    {
        return ['per_alumno_id' => ['bail', 'required', 'integer', 'exists:per_alumno,id']];
    }
}
