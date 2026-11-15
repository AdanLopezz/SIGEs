<?php

namespace App\Http\Requests\Academico;

use App\Models\TrxCalificacion;

// Tabla de captura: calificaciones[inscripción][evaluación]. Qué celdas puede cambiar cada quien y cuándo se exige
// el motivo lo decide TrxCalificacion::guardarTabla().
class GuardarCalificacionesRequest extends CalificacionesRequest
{
    public function authorize(): bool
    {
        $materia = $this->route('materia');

        return $this->user()?->can('capturar', $materia) || $this->user()?->can('corregir', $materia);
    }

    public function rules(): array
    {
        return [
            'calificaciones' => ['required', 'array'],
            'calificaciones.*' => ['array'],
            'calificaciones.*.*' => ['nullable', 'numeric', 'between:0,10', 'decimal:0,'.TrxCalificacion::decimales()],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        $decimales = TrxCalificacion::decimales();

        return parent::messages() + ['calificaciones.required' => 'No hay calificaciones que guardar.',
            'calificaciones.*.*.decimal' => $decimales === 0 ? 'Las calificaciones deben ser enteras.' : 'Cada calificación admite hasta '.$decimales.($decimales === 1 ? ' decimal.' : ' decimales.')];
    }
}
