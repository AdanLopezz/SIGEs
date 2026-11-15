<?php

namespace App\Http\Requests\ControlEscolar;

class FiltrarAlumnosRequest extends ControlEscolarRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('alumnos.ver') ?? false;
    }

    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:100'],
            'grupo' => ['nullable', 'integer', 'exists:aca_grupo_escolar,id'],
            'carrera' => ['nullable', 'integer', 'exists:aca_carrera,id'],
            'semestre' => ['nullable', 'integer', 'between:1,10'],
            'estado' => ['nullable', 'integer', 'exists:cat_estado_alumno,id'],
        ];
    }
}
