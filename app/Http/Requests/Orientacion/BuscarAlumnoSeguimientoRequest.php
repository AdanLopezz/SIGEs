<?php

namespace App\Http\Requests\Orientacion;

use App\Models\TrxSeguimientoAlumno;
use Illuminate\Validation\Rule;

// Buscador del formulario de apertura (GET ?buscar=…&alumno=…&motivo=…); motivo llega desde "Alumnos a atender".
class BuscarAlumnoSeguimientoRequest extends SeguimientoRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('seguimiento.administrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:100'],
            'alumno' => ['nullable', 'integer'],
            'motivo' => ['nullable', Rule::in(array_keys(TrxSeguimientoAlumno::MOTIVOS))],
        ];
    }
}
