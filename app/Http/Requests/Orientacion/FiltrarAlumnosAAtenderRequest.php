<?php

namespace App\Http\Requests\Orientacion;

use App\Support\AlumnosAAtender;
use Illuminate\Validation\Rule;

class FiltrarAlumnosAAtenderRequest extends SeguimientoRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('seguimiento.administrar') ?? false;
    }

    public function rules(): array
    {
        return ['grupo' => ['nullable', 'integer'], 'motivo' => ['nullable', Rule::in(AlumnosAAtender::MOTIVOS)]];
    }
}
