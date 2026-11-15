<?php

namespace App\Http\Requests\Orientacion;

use App\Models\TrxSeguimientoAlumno;
use Illuminate\Validation\Rule;

class FiltrarSeguimientoRequest extends SeguimientoRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('seguimiento.administrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'estado' => ['nullable', Rule::in(array_keys(TrxSeguimientoAlumno::ESTADOS))],
            'motivo' => ['nullable', Rule::in(array_keys(TrxSeguimientoAlumno::MOTIVOS))],
            'grupo' => ['nullable', 'integer'],
            'riesgo' => ['nullable', Rule::in(['1'])],
            'alumno' => ['nullable', 'integer'],
        ];
    }
}
