<?php

namespace App\Http\Requests\Academico;

use Illuminate\Validation\Rule;

// Copiar el horario de otro grupo (mismo semestre y carrera) a un grupo sin horario. Las reglas están en AcaHorario::copiar().
class CopiarHorarioRequest extends AcademicoRequest
{
    public function rules(): array
    {
        return [
            'origen' => ['required', 'integer', 'exists:aca_grupo_escolar,id'],
            'org_espacio_fisico_id' => ['nullable', 'integer', Rule::exists('org_espacio_fisico', 'id')->where('activo', true)],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['origen' => 'grupo origen', 'org_espacio_fisico_id' => 'aula'];
    }

    public function messages(): array
    {
        return ['org_espacio_fisico_id.exists' => 'Elige un aula activa.'] + parent::messages();
    }
}
