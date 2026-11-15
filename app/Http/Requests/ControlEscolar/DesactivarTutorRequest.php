<?php

namespace App\Http\Requests\ControlEscolar;

// Desactivar a un tutor con historial: el motivo queda en la bitácora.
class DesactivarTutorRequest extends ControlEscolarRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('editar', $this->route('alumno')) ?? false;
    }

    public function rules(): array
    {
        return ['motivo' => ['bail', 'required', 'string', 'min:10', 'max:255']];
    }

    public function messages(): array
    {
        return ['motivo.min' => 'Describe el motivo con al menos :min caracteres.'] + parent::messages();
    }
}
