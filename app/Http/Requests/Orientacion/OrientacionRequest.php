<?php

namespace App\Http\Requests\Orientacion;

use Illuminate\Foundation\Http\FormRequest;

abstract class OrientacionRequest extends FormRequest
{
    public const PERMISOS_INCIDENCIAS = ['incidencias.registrar', 'incidencias.cerrar'];

    public function attributes(): array
    {
        return ['per_alumno_id' => 'alumno', 'cat_tipo_incidencia_id' => 'tipo de incidencia', 'descripcion' => 'descripción',
            'fecha_inicio' => 'fecha de inicio', 'fecha_fin' => 'fecha de fin', 'documento' => 'documento', 'motivo' => 'motivo del cambio',
            'resolucion' => 'resolución', 'estado' => 'estado', 'grupo' => 'grupo', 'categoria' => 'categoría', 'gravedad' => 'gravedad',
            'desde' => 'fecha inicial', 'hasta' => 'fecha final', 'alumno' => 'alumno', 'buscar' => 'búsqueda'];
    }

    public function messages(): array
    {
        return ['required' => 'El campo :attribute es obligatorio.', 'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no debe superar :max caracteres.', 'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'integer' => 'Selecciona una opción válida para :attribute.', 'exists' => 'Selecciona una opción válida para :attribute.',
            'in' => 'Selecciona una opción válida para :attribute.', 'date_format' => 'La :attribute no tiene un formato válido.',
            'after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.'];
    }
}
