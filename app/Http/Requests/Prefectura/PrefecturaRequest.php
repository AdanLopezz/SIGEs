<?php

namespace App\Http\Requests\Prefectura;

use Illuminate\Foundation\Http\FormRequest;

abstract class PrefecturaRequest extends FormRequest
{
    // Asistencia: la consulta (asistencia.ver) o quien puede justificar (incidencias.cerrar o alumnos.editar).
    public const PERMISOS_ASISTENCIA = ['asistencia.ver', 'incidencias.cerrar', 'alumnos.editar'];

    public const PERMISOS_JUSTIFICAR = ['incidencias.cerrar', 'alumnos.editar'];

    public function attributes(): array
    {
        return ['codigo' => 'código leído', 'per_alumno_id' => 'alumno', 'buscar' => 'búsqueda', 'fecha' => 'fecha', 'grupo' => 'grupo',
            'motivo' => 'motivo', 'salida_prevista_at' => 'fecha y hora de salida', 'per_tutor_alumno_id' => 'tutor',
            'decision' => 'decisión', 'metodo' => 'método', 'evidencia' => 'evidencia'];
    }

    public function messages(): array
    {
        return ['required' => 'El campo :attribute es obligatorio.', 'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no debe superar :max caracteres.', 'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'integer' => 'Selecciona una opción válida para :attribute.', 'exists' => 'Selecciona una opción válida para :attribute.',
            'in' => 'Selecciona una opción válida para :attribute.', 'date_format' => 'El campo :attribute no tiene un formato válido.',
            'before_or_equal' => 'La :attribute no puede ser futura.', 'after_or_equal' => 'La :attribute no puede estar en el pasado.'];
    }
}
