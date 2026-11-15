<?php

namespace App\Http\Requests\Vinculacion;

use App\Models\TrxAspirante;
use Illuminate\Validation\Rule;

// Vinculación (aspirantes.administrar) y Control Escolar (alumnos.editar, solo lectura y conversión).
class FiltrarAspirantesRequest extends VinculacionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['aspirantes.administrar', 'alumnos.editar']) ?? false;
    }

    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:100'],
            'ciclo' => ['nullable', 'string', 'regex:'.TrxAspirante::CICLO_REGEX],
            'carrera' => ['nullable', 'integer'],
            'medio' => ['nullable', 'integer'],
            // "TODOS" quita el filtro por defecto (REGISTRADO para Control Escolar).
            'estado' => ['nullable', Rule::in([...array_keys(TrxAspirante::ESTADOS), 'TODOS'])],
        ];
    }
}
