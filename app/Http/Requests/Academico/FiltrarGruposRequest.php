<?php

namespace App\Http\Requests\Academico;

class FiltrarGruposRequest extends AcademicoRequest
{
    public function rules(): array
    {
        return [
            'periodo' => ['nullable', 'integer', 'exists:aca_periodo_escolar,id'],
            'carrera' => ['nullable', 'integer', 'exists:aca_carrera,id'],
            'turno' => ['nullable', 'integer', 'exists:aca_turno,id'],
            'semestre' => ['nullable', 'integer', 'between:1,10'],
        ];
    }
}
