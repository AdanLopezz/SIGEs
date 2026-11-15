<?php

namespace App\Http\Requests\Vinculacion;

use App\Models\TrxAspirante;
use Illuminate\Validation\Rule;

// Cambio manual de estado (PROSPECTO, REGISTRADO o DESCARTADO), siempre con motivo.
class EstadoAspiranteRequest extends VinculacionRequest
{
    protected $errorBag = 'estado';

    public function authorize(): bool
    {
        return $this->user()?->can('aspirantes.administrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'estado' => ['bail', 'required', Rule::in(TrxAspirante::ESTADOS_MANUALES)],
            'motivo' => ['bail', 'required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['estado' => 'nuevo estado', 'motivo' => 'motivo'] + parent::attributes();
    }
}
