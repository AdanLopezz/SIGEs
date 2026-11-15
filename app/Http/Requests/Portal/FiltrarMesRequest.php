<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

// Mes del calendario de asistencia (?mes=AAAA-MM). Quién ve a qué alumno lo decide PortalPolicy.
class FiltrarMesRequest extends FormRequest
{
    public function rules(): array
    {
        return ['mes' => ['nullable', 'date_format:Y-m']];
    }

    public function attributes(): array
    {
        return ['mes' => 'mes'];
    }

    public function messages(): array
    {
        return ['date_format' => 'El :attribute no tiene un formato válido (AAAA-MM).'];
    }
}
