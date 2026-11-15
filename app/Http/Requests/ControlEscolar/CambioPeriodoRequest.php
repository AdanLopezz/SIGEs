<?php

namespace App\Http\Requests\ControlEscolar;

use App\Support\CambioDePeriodo;
use Illuminate\Validation\Rule;

// Cambio de periodo (consultas GET): periodos del paso 1 y 2, "conservar orientador y tutor" y filtro del resumen.
class CambioPeriodoRequest extends ControlEscolarRequest
{
    protected $redirectRoute = 'cambio-periodo.index';

    public function authorize(): bool
    {
        return $this->user()?->can('inscripciones.administrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'origen' => ['nullable', 'integer', 'exists:aca_periodo_escolar,id'],
            'destino' => ['nullable', 'integer', 'exists:aca_periodo_escolar,id'],
            'conservar' => ['nullable', 'boolean'],
            'resultado' => ['nullable', Rule::in(array_keys(CambioDePeriodo::RESULTADOS))],
        ];
    }

    public function attributes(): array
    {
        return ['origen' => 'periodo origen', 'destino' => 'periodo destino', 'conservar' => 'conservar orientador y tutor', 'resultado' => 'resultado'] + parent::attributes();
    }
}
