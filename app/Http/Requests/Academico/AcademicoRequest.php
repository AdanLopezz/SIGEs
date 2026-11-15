<?php

namespace App\Http\Requests\Academico;

use Illuminate\Foundation\Http\FormRequest;

abstract class AcademicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('horarios.administrar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('identificador'))) {
            $this->merge(['identificador' => mb_strtoupper(trim($this->input('identificador')))]);
        }
    }

    public function attributes(): array
    {
        return ['aca_periodo_escolar_id' => 'periodo', 'periodo' => 'periodo', 'aca_carrera_id' => 'carrera', 'carrera' => 'carrera',
            'aca_turno_id' => 'turno', 'turno' => 'turno', 'semestre' => 'semestre', 'identificador' => 'identificador',
            'orientador_id' => 'orientador', 'tutor_id' => 'tutor', 'aca_asignatura_id' => 'asignatura', 'docente_id' => 'docente'];
    }

    public function messages(): array
    {
        return ['required' => 'El campo :attribute es obligatorio.', 'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no debe superar :max caracteres.',
            'integer' => 'Selecciona una opción válida para :attribute.', 'between' => 'El campo :attribute debe estar entre :min y :max.',
            'exists' => 'Selecciona una opción válida para :attribute.', 'unique' => 'El valor de :attribute ya está registrado.',
            'identificador.regex' => 'El identificador solo admite letras y números, sin espacios (ej. 1A).',
            'identificador.unique' => 'Ya existe un grupo con ese identificador en el periodo seleccionado.',
            'aca_carrera_id.exists' => 'Selecciona una carrera activa.',
            'orientador_id.in' => 'Selecciona a una persona activa con el rol Orientador.',
            'tutor_id.in' => 'Selecciona a una persona activa con el rol Tutor.',
            'docente_id.in' => 'Selecciona a una persona activa con el rol Docente.',
            'aca_asignatura_id.exists' => 'Selecciona una asignatura activa del semestre del grupo.',
            'aca_asignatura_id.unique' => 'Esta asignatura ya está asignada al grupo.'];
    }
}
