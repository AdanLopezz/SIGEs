<?php

namespace App\Http\Requests\Academico;

// Asistencia por materia: lista de materias (grupo, materia) y tabla de una materia (mes AAAA-MM). GET.
class FiltrarAsistenciaMateriaRequest extends AsistenciaClaseRequest
{
    public function rules(): array
    {
        return [
            'grupo' => ['nullable', 'integer'],
            'asignatura' => ['nullable', 'integer'],
            'mes' => ['nullable', 'date_format:Y-m'],
        ];
    }
}
