<?php

namespace App\Http\Requests\Prefectura;

use App\Models\PerAlumno;

class PermisoSalidaRequest extends PrefecturaRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('permisos_salida.administrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'per_alumno_id' => ['bail', 'required', 'integer', function ($atributo, $valor, $fallar) {
                if (! PerAlumno::whereKey($valor)->visiblesPara($this->user())->exists()) {
                    $fallar('Selecciona un alumno de tus grupos.');
                }
            }],
            'motivo' => ['bail', 'required', 'string', 'min:5', 'max:1000'],
            // Al minuto: "ahora" todavía es válido.
            'salida_prevista_at' => ['bail', 'required', 'date_format:Y-m-d\TH:i', 'after_or_equal:'.now()->format('Y-m-d H:i')],
        ];
    }

    public function messages(): array
    {
        return ['salida_prevista_at.after_or_equal' => 'La salida prevista debe ser hoy (a partir de ahora) o después.'] + parent::messages();
    }
}
