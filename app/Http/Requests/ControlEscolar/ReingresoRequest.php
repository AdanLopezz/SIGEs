<?php

namespace App\Http\Requests\ControlEscolar;

use App\Models\AcaPeriodoEscolar;
use Illuminate\Validation\Rule;

class ReingresoRequest extends ControlEscolarRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('bajas.registrar') && $this->user()->can('ver', $this->route('alumno'))) ?? false;
    }

    public function rules(): array
    {
        return [
            'aca_grupo_escolar_id' => ['bail', 'required', 'integer', Rule::exists('aca_grupo_escolar', 'id')
                ->where('aca_carrera_id', $this->route('alumno')->aca_carrera_id)
                ->where('aca_periodo_escolar_id', AcaPeriodoEscolar::activo()?->id ?? 0)],
            // El reingreso no tiene columna propia: el motivo queda en la bitácora.
            'motivo' => ['required', 'string', 'max:255'],
        ];
    }
}
