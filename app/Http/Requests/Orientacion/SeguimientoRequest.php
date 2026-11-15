<?php

namespace App\Http\Requests\Orientacion;

use App\Models\TrxSeguimientoNota;
use Illuminate\Validation\Rule;

// Base de los Requests de Seguimiento del alumno.
abstract class SeguimientoRequest extends OrientacionRequest
{
    public function attributes(): array
    {
        return ['riesgo_abandono' => 'riesgo de abandono', 'tipo_atencion' => 'tipo de atención', 'acuerdos' => 'acuerdos',
            'fecha' => 'fecha', 'riesgo' => 'riesgo de abandono', 'confirmar_duplicado' => 'confirmación'] + parent::attributes();
    }

    public function messages(): array
    {
        return parent::messages() + ['boolean' => 'Selecciona una opción válida para :attribute.'];
    }

    // Nota de atención (también la nota final al cerrar o continuar).
    protected function reglasDeNota(): array
    {
        return [
            'tipo_atencion' => ['bail', 'required', Rule::in(array_keys(TrxSeguimientoNota::TIPOS))],
            'descripcion' => ['bail', 'required', 'string', 'min:10', 'max:4000'],
            'acuerdos' => ['bail', 'required', 'string', 'min:5', 'max:4000'],
        ];
    }
}
