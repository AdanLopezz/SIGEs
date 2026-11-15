<?php

namespace App\Http\Requests\Academico;

use App\Policies\GrupoAsignaturaPolicy;

// Filtros de Evaluaciones y de Calificaciones (GET).
class FiltrarMateriasRequest extends CalificacionesRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(GrupoAsignaturaPolicy::PERMISOS) ?? false;
    }

    public function rules(): array
    {
        return ['grupo' => ['nullable', 'integer'], 'asignatura' => ['nullable', 'integer']];
    }
}
