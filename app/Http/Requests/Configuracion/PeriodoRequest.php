<?php

namespace App\Http\Requests\Configuracion;

use App\Models\AcaPeriodoEscolar;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PeriodoRequest extends ConfiguracionRequest
{
    public function rules(): array
    {
        return [
            'clave' => ['bail', 'required', 'string', 'max:30', Rule::unique('aca_periodo_escolar', 'clave')->ignore($this->route('periodo')?->id), 'regex:/^\d{4}-[12]$/'],
            'fecha_inicio' => ['bail', 'required', 'date_format:Y-m-d'],
            'fecha_fin' => ['bail', 'required', 'date_format:Y-m-d', 'after:fecha_inicio'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validador) {
            if ($validador->errors()->hasAny(['fecha_inicio', 'fecha_fin'])) {
                return;
            }
            if (AcaPeriodoEscolar::seEncima($this->input('fecha_inicio'), $this->input('fecha_fin'), $this->route('periodo')?->id)) {
                $validador->errors()->add('fecha_fin', 'Las fechas se enciman con otro periodo escolar.');
            }
        }];
    }
}
