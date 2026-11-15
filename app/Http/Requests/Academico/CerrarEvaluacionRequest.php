<?php

namespace App\Http\Requests\Academico;

class CerrarEvaluacionRequest extends CalificacionesRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('cerrarEvaluacion', $this->route('evaluacion')->materia) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
