<?php

namespace App\Http\Requests\ControlEscolar;

// Asignar recursamiento. Que el destino imparta la misma asignatura en el periodo activo lo revisa TrxResultadoAsignatura.
class RecursamientoRequest extends RegularizacionRequest
{
    public function rules(): array
    {
        return [
            'aca_grupo_asignatura_id' => ['bail', 'required', 'integer'],
            'fecha_inicio' => ['bail', 'required', 'date_format:Y-m-d'],
        ];
    }
}
