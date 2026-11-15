<?php

namespace App\Http\Requests\ControlEscolar;

use App\Models\AcaPeriodoEscolar;
use Illuminate\Validation\Rule;

class InscripcionRequest extends ControlEscolarRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('inscripciones.administrar') && $this->user()->can('ver', $this->route('alumno'))) ?? false;
    }

    public function rules(): array
    {
        return [
            'aca_grupo_escolar_id' => ['bail', 'required', 'integer', Rule::exists('aca_grupo_escolar', 'id')
                ->where('aca_carrera_id', $this->route('alumno')->aca_carrera_id)
                ->where('aca_periodo_escolar_id', AcaPeriodoEscolar::activo()?->id ?? 0)],
            // El cambio de grupo (PUT) queda en la bitácora con su motivo.
            'motivo' => $this->isMethod('put') ? ['required', 'string', 'max:255'] : ['exclude'],
        ];
    }
}
