<?php

namespace App\Http\Requests\Orientacion;

use App\Models\Archivo;

// Alta y edición. Las reglas de fechas por categoría, del periodo y del documento obligatorio las aplica TrxIncidencia.
class IncidenciaRequest extends OrientacionRequest
{
    public function authorize(): bool
    {
        $incidencia = $this->route('incidencia');

        return $incidencia
            ? $this->user()?->can('editar', $incidencia) ?? false
            : $this->user()?->can('incidencias.registrar') ?? false;
    }

    public function rules(): array
    {
        $editando = $this->route('incidencia') !== null;

        return [
            'per_alumno_id' => $editando ? ['exclude'] : ['bail', 'required', 'integer'],
            'cat_tipo_incidencia_id' => ['bail', 'required', 'integer', 'exists:cat_tipo_incidencia,id'],
            'descripcion' => ['bail', 'required', 'string', 'min:10', 'max:2000'],
            'fecha_inicio' => ['bail', 'required', 'date_format:Y-m-d'],
            'fecha_fin' => ['nullable', 'date_format:Y-m-d'],
            'documento' => Archivo::reglas(false),
            // Queda en la bitácora; solo se pide al editar.
            'motivo' => $editando ? ['required', 'string', 'max:255'] : ['exclude'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + Archivo::mensajes('documento');
    }
}
