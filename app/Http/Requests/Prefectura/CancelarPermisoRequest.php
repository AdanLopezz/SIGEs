<?php

namespace App\Http\Requests\Prefectura;

class CancelarPermisoRequest extends PrefecturaRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('permisos_salida.administrar') && $this->route('permiso')->esVisiblePara($this->user())) ?? false;
    }

    public function rules(): array
    {
        return ['motivo' => ['bail', 'required', 'string', 'max:255']];
    }

    public function attributes(): array
    {
        return ['motivo' => 'motivo de la cancelación'] + parent::attributes();
    }
}
