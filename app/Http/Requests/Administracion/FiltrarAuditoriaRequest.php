<?php

namespace App\Http\Requests\Administracion;

use App\Support\Auditoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FiltrarAuditoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('auditoria.ver') ?? false;
    }

    public function rules(): array
    {
        return [
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'usuario' => ['nullable', 'integer'],
            'tabla' => ['nullable', Rule::in(array_keys(Auditoria::TABLAS))],
            'accion' => ['nullable', Rule::in(array_keys(Auditoria::ACCIONES))],
        ];
    }

    public function attributes(): array
    {
        return ['desde' => 'fecha inicial', 'hasta' => 'fecha final', 'usuario' => 'usuario', 'tabla' => 'tabla', 'accion' => 'acción'];
    }

    public function messages(): array
    {
        return ['date_format' => 'La :attribute no tiene un formato válido.', 'after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
            'integer' => 'Selecciona un :attribute válido.', 'in' => 'Selecciona una opción válida para :attribute.'];
    }
}
