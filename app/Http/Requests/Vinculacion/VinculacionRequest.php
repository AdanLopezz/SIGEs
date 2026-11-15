<?php

namespace App\Http\Requests\Vinculacion;

use App\Http\Requests\ControlEscolar\ControlEscolarRequest;

// Base de Vinculación: reutiliza las reglas de persona y CURP de Control Escolar (aspirantes) y normaliza RFC y correos.
abstract class VinculacionRequest extends ControlEscolarRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        foreach (['rfc', 'numero'] as $campo) {
            if (is_string($this->input($campo))) {
                $this->merge([$campo => mb_strtoupper(trim($this->input($campo)))]);
            }
        }
        if (is_string($this->input('contacto_correo'))) {
            $this->merge(['contacto_correo' => mb_strtolower(trim($this->input('contacto_correo')))]);
        }
    }

    public function attributes(): array
    {
        return ['nombre' => 'nombre', 'rfc' => 'RFC', 'contacto_nombre' => 'nombre del contacto', 'contacto_telefono' => 'teléfono del contacto',
            'contacto_correo' => 'correo del contacto', 'org_empresa_id' => 'empresa', 'numero' => 'número de convenio', 'tipo' => 'tipo',
            'fecha_inicio' => 'fecha de inicio', 'fecha_fin' => 'fecha de fin', 'documento' => 'convenio firmado', 'empresa' => 'empresa',
            'cat_medio_difusion_id' => 'medio de difusión', 'escuela_procedencia' => 'escuela de procedencia', 'ciclo_ingreso' => 'ciclo de ingreso',
            'ciclo' => 'ciclo', 'medio' => 'medio de difusión'] + parent::attributes();
    }

    public function messages(): array
    {
        return parent::messages() + ['after' => 'La fecha de fin debe ser posterior a la de inicio.', 'mimes' => 'El archivo debe ser PDF, JPG o PNG.'];
    }
}
