<?php

namespace App\Http\Requests\Prefectura;

class FiltrarAsistenciaRequest extends PrefecturaRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(self::PERMISOS_ASISTENCIA) ?? false;
    }

    public function rules(): array
    {
        return [
            'fecha' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'grupo' => ['nullable', 'integer'],
        ];
    }
}
