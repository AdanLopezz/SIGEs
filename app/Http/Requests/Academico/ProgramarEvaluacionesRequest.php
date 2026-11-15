<?php

namespace App\Http\Requests\Academico;

// Pesos por tipo de evaluación. La suma de 100 y las evaluaciones bloqueadas las revisa AcaEvaluacion::programar().
class ProgramarEvaluacionesRequest extends CalificacionesRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('programar', $this->route('materia')) ?? false;
    }

    public function rules(): array
    {
        return [
            'pesos' => ['required', 'array'],
            'pesos.*' => ['nullable', 'numeric', 'between:0.01,100', 'decimal:0,2'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + ['pesos.required' => 'Escribe el peso de al menos una evaluación.'];
    }
}
