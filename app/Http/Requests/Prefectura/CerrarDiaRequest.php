<?php

namespace App\Http\Requests\Prefectura;

class CerrarDiaRequest extends PrefecturaRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('acceso.registrar') ?? false;
    }

    public function rules(): array
    {
        return ['fecha' => ['bail', 'required', 'date_format:Y-m-d', 'before_or_equal:today']];
    }
}
