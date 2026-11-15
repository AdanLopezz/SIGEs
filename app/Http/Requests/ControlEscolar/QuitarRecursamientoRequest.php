<?php

namespace App\Http\Requests\ControlEscolar;

class QuitarRecursamientoRequest extends RegularizacionRequest
{
    public function rules(): array
    {
        return ['motivo' => ['bail', 'required', 'string', 'min:10', 'max:255']];
    }
}
