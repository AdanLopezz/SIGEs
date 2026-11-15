<?php

namespace App\Http\Requests\Academico;

// Horario por docente o por aula (Control Escolar). GET.
class FiltrarHorarioConsultaRequest extends AcademicoRequest
{
    public function rules(): array
    {
        return [
            'periodo' => ['nullable', 'integer', 'exists:aca_periodo_escolar,id'],
            'docente' => ['nullable', 'integer'],
            'aula' => ['nullable', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['docente' => 'docente', 'aula' => 'aula'];
    }
}
