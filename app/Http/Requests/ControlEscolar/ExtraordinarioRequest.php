<?php

namespace App\Http\Requests\ControlEscolar;

// Registrar o corregir el extraordinario. Las reglas del resultado, del periodo y de la fecha las aplica TrxResultadoAsignatura.
class ExtraordinarioRequest extends RegularizacionRequest
{
    public function rules(): array
    {
        return [
            'calificacion' => $this->reglasCalificacion(),
            'fecha' => ['bail', 'required', 'date_format:Y-m-d'],
            'motivo' => ['bail', 'required', 'string', 'min:10', 'max:255'],
        ];
    }
}
