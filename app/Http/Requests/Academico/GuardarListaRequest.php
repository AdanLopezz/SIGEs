<?php

namespace App\Http\Requests\Academico;

// Guardar el pase de lista de una materia en una fecha. El motivo lo exige el modelo cuando corresponde.
class GuardarListaRequest extends AsistenciaClaseRequest
{
    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date_format:Y-m-d'],
            'marcas' => ['nullable', 'array'],
            'marcas.*' => ['required', 'string', 'in:A,F,R,J'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }
}
