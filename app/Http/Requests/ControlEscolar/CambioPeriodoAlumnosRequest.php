<?php

namespace App\Http\Requests\ControlEscolar;

// Cambio de periodo, paso 3: casilla y grupo destino de los alumnos de un grupo del origen (las llaves son los alumnos).
class CambioPeriodoAlumnosRequest extends ControlEscolarRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inscripciones.administrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'alumnos' => ['nullable', 'array'],
            'alumnos.*' => ['array'],
            'alumnos.*.marcado' => ['nullable', 'boolean'],
            'alumnos.*.destino' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return ['alumnos.*.marcado' => 'reinscribir', 'alumnos.*.destino' => 'grupo destino'] + parent::attributes();
    }
}
