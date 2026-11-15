<?php

namespace App\Http\Requests\ControlEscolar;

// Cambio de periodo, paso 2: identificador, orientador y tutor de cada grupo propuesto (las llaves son los grupos del origen).
class CambioPeriodoGruposRequest extends ControlEscolarRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inscripciones.administrar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $grupos = $this->input('grupos');
        if (is_array($grupos)) {
            foreach ($grupos as $id => $datos) {
                if (is_array($datos) && is_string($datos['identificador'] ?? null)) {
                    $grupos[$id]['identificador'] = mb_strtoupper(trim($datos['identificador']));
                }
            }
            $this->merge(['grupos' => $grupos]);
        }
    }

    public function rules(): array
    {
        return [
            'grupos' => ['required', 'array'],
            'grupos.*' => ['array'],
            'grupos.*.identificador' => ['bail', 'required', 'string', 'max:20', 'regex:/^[0-9A-Z]+$/'],
            'grupos.*.orientador_id' => ['nullable', 'integer'],
            'grupos.*.tutor_id' => ['nullable', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return ['grupos' => 'grupos', 'grupos.*.identificador' => 'identificador', 'grupos.*.orientador_id' => 'orientador', 'grupos.*.tutor_id' => 'tutor'] + parent::attributes();
    }

    public function messages(): array
    {
        return ['grupos.*.identificador.regex' => 'El identificador solo admite letras mayúsculas y números.'] + parent::messages();
    }
}
