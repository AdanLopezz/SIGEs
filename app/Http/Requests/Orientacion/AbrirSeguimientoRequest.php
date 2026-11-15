<?php

namespace App\Http\Requests\Orientacion;

use App\Models\TrxSeguimientoAlumno;
use Illuminate\Validation\Rule;

// Alumno elegible, periodo activo y aviso de caso abierto con el mismo motivo: TrxSeguimientoAlumno::abrir().
class AbrirSeguimientoRequest extends SeguimientoRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('seguimiento.administrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'per_alumno_id' => ['bail', 'required', 'integer'],
            'motivo' => ['bail', 'required', Rule::in(array_keys(TrxSeguimientoAlumno::MOTIVOS))],
            'descripcion' => ['bail', 'required', 'string', 'min:10', 'max:4000'],
            'riesgo_abandono' => ['bail', 'required', 'boolean'],
            'confirmar_duplicado' => ['nullable', 'boolean'],
        ];
    }

    // Aquí "motivo" es el motivo del caso, no el de la bitácora.
    public function attributes(): array
    {
        return ['motivo' => 'motivo del caso'] + parent::attributes();
    }
}
