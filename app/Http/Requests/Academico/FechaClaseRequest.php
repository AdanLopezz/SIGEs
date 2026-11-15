<?php

namespace App\Http\Requests\Academico;

// "Mis clases de hoy" y la lista de una materia (GET ?fecha=AAAA-MM-DD; sin fecha, hoy).
class FechaClaseRequest extends AsistenciaClaseRequest
{
    public function rules(): array
    {
        return ['fecha' => ['nullable', 'date_format:Y-m-d']];
    }
}
