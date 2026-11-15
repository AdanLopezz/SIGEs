<?php

namespace App\Http\Requests\ControlEscolar;

use App\Models\Archivo;
use App\Models\PerConsentimiento;
use Illuminate\Validation\Rule;

class ConsentimientoRequest extends ControlEscolarRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('editar', $this->route('alumno')) ?? false;
    }

    public function rules(): array
    {
        return [
            'per_tutor_alumno_id' => ['bail', 'required', 'integer', Rule::exists('per_tutor_alumno', 'id')->where('per_alumno_id', $this->route('alumno')->id)->where('activo', true)],
            'tipo' => ['required', Rule::in(array_keys(PerConsentimiento::TIPOS))],
            'otorgado' => ['required', 'boolean'],
            'fecha' => ['bail', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'documento' => Archivo::reglas(),
        ];
    }

    public function attributes(): array
    {
        return ['per_tutor_alumno_id' => 'tutor', 'tipo' => 'tipo de consentimiento',
            'otorgado' => 'sentido del consentimiento', 'fecha' => 'fecha de firma', 'documento' => 'documento firmado'] + parent::attributes();
    }

    public function messages(): array
    {
        return ['otorgado.required' => 'Indica si el consentimiento se otorga o se revoca.',
            'fecha.before_or_equal' => 'La fecha de firma no puede ser futura.'] + parent::messages() + Archivo::mensajes('documento');
    }
}
