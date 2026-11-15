<?php

namespace App\Http\Requests\Academico;

use App\Models\PerPersonal;
use Illuminate\Validation\Rule;

class GrupoAsignaturaRequest extends AcademicoRequest
{
    public function rules(): array
    {
        $grupo = $this->route('grupo');

        return [
            // En la edición solo cambia el docente; la asignatura queda fija.
            'aca_asignatura_id' => $this->route('materia') ? ['exclude'] : ['bail', 'required', 'integer',
                Rule::exists('aca_asignatura', 'id')->where('activo', true)->where('semestre', $grupo->semestre),
                Rule::unique('aca_grupo_asignatura', 'aca_asignatura_id')->where('aca_grupo_escolar_id', $grupo->id)],
            'docente_id' => ['bail', 'required', 'integer', Rule::in(PerPersonal::conRol('Docente')->pluck('id')->all())],
        ];
    }
}
