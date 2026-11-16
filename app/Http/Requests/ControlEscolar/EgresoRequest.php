<?php

namespace App\Http\Requests\ControlEscolar;

use Illuminate\Foundation\Http\FormRequest;

class EgresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('egreso.administrar') ?? false;
    }

    public function messages(): array
    {
        return ['required' => 'El campo :attribute es obligatorio.', 'exists' => 'Selecciona un valor válido para :attribute.',
            'in' => 'Selecciona un valor válido para :attribute.', 'date_format' => 'La fecha de :attribute no es válida.',
            'after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'before_or_equal' => 'La fecha de :attribute no puede ser futura.', 'integer' => 'El campo :attribute debe ser entero.',
            'between' => 'El campo :attribute debe estar entre :min y :max.', 'max' => 'El campo :attribute supera el máximo permitido.',
            'accepted' => 'Debes confirmar :attribute.', 'decimal' => 'El campo :attribute admite hasta dos decimales.'];
    }

    public function attributes(): array
    {
        return ['fecha_egreso' => 'egreso', 'promedio_general' => 'promedio general', 'motivo' => 'motivo',
            'historial_verificado' => 'la revisión del historial oficial', 'org_convenio_id' => 'convenio',
            'horas_acreditadas' => 'horas acreditadas', 'fecha_inicio' => 'inicio', 'fecha_fin' => 'fin'];
    }
}
