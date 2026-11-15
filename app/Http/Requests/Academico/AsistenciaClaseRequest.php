<?php

namespace App\Http\Requests\Academico;

use Illuminate\Foundation\Http\FormRequest;

// Pase de lista y asistencia por materia: el docente (asistencia_clase.registrar) o Control Escolar (asistencia_clase.corregir).
// Qué materia y qué fecha lo deciden GrupoAsignaturaPolicy y TrxAsistenciaClase.
abstract class AsistenciaClaseRequest extends FormRequest
{
    public const PERMISOS = ['asistencia_clase.registrar', 'asistencia_clase.corregir'];

    public function authorize(): bool
    {
        return $this->user()?->canAny(self::PERMISOS) ?? false;
    }

    public function attributes(): array
    {
        return ['fecha' => 'fecha', 'mes' => 'mes', 'grupo' => 'grupo', 'asignatura' => 'materia', 'marcas' => 'lista',
            'marcas.*' => 'marca', 'motivo' => 'motivo'];
    }

    public function messages(): array
    {
        return ['required' => 'El campo :attribute es obligatorio.', 'integer' => 'Selecciona una opción válida para :attribute.',
            'date_format' => 'La :attribute no es válida.', 'array' => 'La :attribute no es válida.', 'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no debe superar :max caracteres.', 'marcas.*.in' => 'Cada marca debe ser A, F, R o J.'];
    }
}
