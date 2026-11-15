<?php

namespace App\Http\Requests\Academico;

use App\Models\AcaCarrera;
use App\Models\PerPersonal;
use Illuminate\Validation\Rule;
use App\Support\PeriodoDeTrabajo;
use Illuminate\Validation\Validator;

class GrupoRequest extends AcademicoRequest
{
    public function rules(): array
    {
        $grupo = $this->route('grupo');
        $periodoBloqueado = $grupo?->tieneAlumnos() ?? false;
        $periodo = $periodoBloqueado ? $grupo->aca_periodo_escolar_id : $this->input('aca_periodo_escolar_id');

        return [
            // Solo el periodo activo o uno futuro (PeriodoDeTrabajo::periodosPreparables).
            'aca_periodo_escolar_id' => $periodoBloqueado ? ['exclude'] : ['bail', 'required', 'integer', Rule::in(PeriodoDeTrabajo::periodosPreparables()->pluck('id')->all())],
            // Un grupo existente puede conservar su carrera aunque se haya desactivado.
            'aca_carrera_id' => ['bail', 'required', 'integer', Rule::exists('aca_carrera', 'id')->where(fn ($consulta) => $consulta
                ->where('activo', true)->when($grupo, fn ($consulta) => $consulta->orWhere('id', $grupo->aca_carrera_id)))],
            'aca_turno_id' => ['bail', 'required', 'integer', 'exists:aca_turno,id'],
            'semestre' => ['bail', 'required', 'integer', 'between:1,10'],
            'identificador' => ['bail', 'required', 'string', 'max:20', 'regex:/^[0-9A-Z]+$/',
                Rule::unique('aca_grupo_escolar', 'identificador')->where('aca_periodo_escolar_id', is_numeric($periodo) ? (int) $periodo : 0)->ignore($grupo?->id)],
            'orientador_id' => ['bail', 'required', 'integer', Rule::in(PerPersonal::conRol('Orientador')->pluck('id')->all())],
            'tutor_id' => ['bail', 'required', 'integer', Rule::in(PerPersonal::conRol('Tutor')->pluck('id')->all())],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validador) {
            if ($validador->errors()->hasAny(['aca_carrera_id', 'semestre'])) {
                return;
            }
            $grupo = $this->route('grupo');
            $totalSemestres = AcaCarrera::whereKey($this->input('aca_carrera_id'))->value('total_semestres');
            if ((int) $this->input('semestre') > $totalSemestres) {
                $validador->errors()->add('semestre', "La carrera seleccionada tiene {$totalSemestres} semestres.");
            } elseif ($grupo && (int) $this->input('semestre') !== $grupo->semestre && $grupo->tieneMaterias()) {
                $validador->errors()->add('semestre', 'No se puede cambiar el semestre porque el grupo ya tiene materias.');
            }
        }];
    }
}
