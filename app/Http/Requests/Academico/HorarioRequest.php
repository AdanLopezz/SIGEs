<?php

namespace App\Http\Requests\Academico;

use App\Models\AcaGrupoEscolar;
use App\Models\AcaHorario;
use Illuminate\Validation\Rule;

// Alta (ruta con {grupo}) y edición (ruta con {horario}) de una clase del horario. Las reglas del negocio están en AcaHorario::guardar().
class HorarioRequest extends AcademicoRequest
{
    public function rules(): array
    {
        $grupo = $this->route('grupo');
        $horario = $this->route('horario');
        $grupoId = $grupo instanceof AcaGrupoEscolar ? $grupo->id : ($horario instanceof AcaHorario ? $horario->materia()->value('aca_grupo_escolar_id') : 0);

        return [
            'aca_grupo_asignatura_id' => ['required', 'integer', Rule::exists('aca_grupo_asignatura', 'id')->where('aca_grupo_escolar_id', $grupoId)],
            'org_espacio_fisico_id' => ['required', 'integer', Rule::exists('org_espacio_fisico', 'id')->where('activo', true)],
            'dia_semana' => ['required', 'integer', 'between:1,6'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['aca_grupo_asignatura_id' => 'materia', 'org_espacio_fisico_id' => 'aula', 'dia_semana' => 'día',
            'hora_inicio' => 'hora de inicio', 'hora_fin' => 'hora de fin'];
    }

    public function messages(): array
    {
        return ['date_format' => 'Escribe la :attribute en formato HH:MM.', 'hora_fin.after' => 'La hora de fin debe ser posterior a la de inicio.',
            'aca_grupo_asignatura_id.exists' => 'Elige una materia del grupo.', 'org_espacio_fisico_id.exists' => 'Elige un aula activa.',
            'dia_semana.between' => 'Elige un día de lunes a sábado.'] + parent::messages();
    }
}
