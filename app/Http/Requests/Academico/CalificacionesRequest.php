<?php

namespace App\Http\Requests\Academico;

use Illuminate\Foundation\Http\FormRequest;

// Evaluaciones y calificaciones. La autorización de cada acción la decide GrupoAsignaturaPolicy.
abstract class CalificacionesRequest extends FormRequest
{
    public function attributes(): array
    {
        return ['grupo' => 'grupo', 'asignatura' => 'materia', 'pesos' => 'pesos', 'pesos.*' => 'peso',
            'calificaciones' => 'calificaciones', 'calificaciones.*.*' => 'calificación', 'motivo' => 'motivo de la corrección'];
    }

    public function messages(): array
    {
        return ['integer' => 'Selecciona una opción válida para :attribute.', 'array' => 'El campo :attribute no es válido.',
            'numeric' => 'Cada :attribute debe ser un número.', 'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no debe superar :max caracteres.',
            'pesos.*.between' => 'Cada peso debe estar entre 0.01 y 100.', 'pesos.*.decimal' => 'Cada peso admite hasta 2 decimales.',
            'calificaciones.*.*.between' => 'Cada calificación debe estar entre 0 y 10.'];
    }
}
