<?php

namespace App\Http\Requests\ControlEscolar;

use App\Models\TrxCalificacion;

// Regularización (extraordinarios y recursamientos): todo con recursamientos.administrar.
abstract class RegularizacionRequest extends ControlEscolarRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('recursamientos.administrar') ?? false;
    }

    // Calificación del extraordinario: de 0 a 10 con los decimales de config/siges.php.
    protected function reglasCalificacion(): array
    {
        return ['bail', 'required', 'numeric', 'between:0,10', 'decimal:0,'.TrxCalificacion::decimales()];
    }

    public function attributes(): array
    {
        return ['calificacion' => 'calificación', 'fecha' => 'fecha del examen', 'motivo' => 'motivo', 'aca_grupo_asignatura_id' => 'grupo destino',
            'fecha_inicio' => 'fecha de inicio', 'periodo' => 'periodo', 'grupo' => 'grupo', 'asignatura' => 'materia',
            'oportunidad' => 'oportunidad', 'situacion' => 'situación'] + parent::attributes();
    }

    public function messages(): array
    {
        $decimales = TrxCalificacion::decimales();

        return ['numeric' => 'La :attribute debe ser un número.',
            'calificacion.decimal' => $decimales === 0 ? 'La calificación debe ser entera.' : 'La calificación admite hasta '.$decimales.($decimales === 1 ? ' decimal.' : ' decimales.'),
            'motivo.min' => 'Describe el motivo con al menos :min caracteres.'] + parent::messages();
    }
}
