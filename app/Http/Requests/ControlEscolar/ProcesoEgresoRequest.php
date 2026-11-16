<?php

namespace App\Http\Requests\ControlEscolar;

use App\Models\TrxProcesoEgreso;
use Illuminate\Validation\Rule;

class ProcesoEgresoRequest extends EgresoRequest
{
    public function rules(): array
    {
        return ['tipo' => ['required', Rule::in(array_keys(TrxProcesoEgreso::TIPOS))],
            'org_convenio_id' => ['required', 'integer', 'exists:org_convenio,id'],
            'estado' => ['required', Rule::in(array_keys(TrxProcesoEgreso::ESTADOS))],
            'horas_acreditadas' => ['required', 'integer', 'between:0,32767'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'motivo' => ['required', 'string', 'max:255']];
    }
}
