<?php

namespace App\Http\Requests\ControlEscolar;

use App\Models\AcaBaja;
use Illuminate\Validation\Rule;

class FiltrarBajasRequest extends ControlEscolarRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('bajas.registrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'periodo' => ['nullable', 'integer', 'exists:aca_periodo_escolar,id'],
            'tipo' => ['nullable', Rule::in(array_keys(AcaBaja::TIPOS))],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ];
    }

    public function attributes(): array
    {
        return ['periodo' => 'periodo', 'tipo' => 'tipo', 'desde' => 'fecha inicial', 'hasta' => 'fecha final'] + parent::attributes();
    }

    public function messages(): array
    {
        return ['hasta.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.'] + parent::messages();
    }
}
