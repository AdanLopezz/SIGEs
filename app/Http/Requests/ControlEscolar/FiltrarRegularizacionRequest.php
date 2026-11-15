<?php

namespace App\Http\Requests\ControlEscolar;

use App\Models\TrxResultadoAsignatura;
use Illuminate\Validation\Rule;

class FiltrarRegularizacionRequest extends RegularizacionRequest
{
    // "TODAS" muestra cualquier situación; sin filtro se muestran las pendientes.
    public const TODAS = 'TODAS';

    public function rules(): array
    {
        return [
            'periodo' => ['nullable', 'integer'],
            'grupo' => ['nullable', 'integer'],
            'asignatura' => ['nullable', 'integer'],
            'oportunidad' => ['nullable', Rule::in(array_keys(TrxResultadoAsignatura::OPORTUNIDADES))],
            'situacion' => ['nullable', Rule::in([...array_keys(TrxResultadoAsignatura::SITUACIONES), self::TODAS])],
        ];
    }
}
