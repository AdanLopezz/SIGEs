<?php

namespace App\Http\Requests\ControlEscolar;

// Cambio de periodo: confirmación. La casilla de materias sin cerrar la exige el asistente solo si las hay.
class CambioPeriodoConfirmarRequest extends ControlEscolarRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inscripciones.administrar') ?? false;
    }

    public function rules(): array
    {
        return ['acepto_pendientes' => ['nullable', 'boolean']];
    }

    public function attributes(): array
    {
        return ['acepto_pendientes' => 'confirmación de materias sin cerrar'] + parent::attributes();
    }
}
