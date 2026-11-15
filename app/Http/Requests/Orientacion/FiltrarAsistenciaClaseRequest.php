<?php

namespace App\Http\Requests\Orientacion;

// Asistencia por clase de los alumnos de mis grupos (Tutor, Orientador, Prefectura y Docente con asistencia.ver). GET.
class FiltrarAsistenciaClaseRequest extends OrientacionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('asistencia.ver') ?? false;
    }

    public function rules(): array
    {
        return [
            'grupo' => ['nullable', 'integer'],
            'asignatura' => ['nullable', 'integer'],
            'bajo_minimo' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['asignatura' => 'materia', 'bajo_minimo' => 'solo bajo el mínimo'];
    }
}
