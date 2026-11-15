<?php

namespace App\Http\Requests\ControlEscolar;

use App\Models\PerPersona;
use App\Models\PerTutorAlumno;
use Illuminate\Validation\Rule;

class TutorAlumnoRequest extends ControlEscolarRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('editar', $this->route('alumno')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        foreach (['es_contacto_emergencia', 'recibe_notificaciones', 'mismo_domicilio'] as $campo) {
            $this->merge([$campo => $this->boolean($campo)]);
        }
    }

    public function rules(): array
    {
        $alumno = $this->route('alumno');
        $banderas = [
            'parentesco' => ['required', Rule::in(array_keys(PerTutorAlumno::PARENTESCOS))],
            'es_contacto_emergencia' => ['boolean'],
            'recibe_notificaciones' => ['boolean'],
        ];
        // En la edición solo cambian el parentesco y las banderas.
        if ($this->route('tutor')) {
            return $banderas;
        }

        $existe = is_string($this->input('curp')) && PerPersona::where('curp', $this->input('curp'))->exists();
        $persona = $this->reglasPersona('', true);

        return [
            'curp' => $this->reglasCurp(function ($atributo, $valor, $fallar) use ($alumno) {
                if ($valor === $alumno->persona->curp) {
                    $fallar('El tutor no puede ser el mismo alumno.');
                } elseif ($alumno->tutores()->whereHas('persona', fn ($personas) => $personas->where('curp', $valor))->exists()) {
                    $fallar('Esta persona ya está registrada como tutor del alumno.');
                }
            }),
            'mismo_domicilio' => ['boolean'],
        ] + $banderas + ($existe ? array_fill_keys(array_keys($persona), ['exclude']) : $persona);
    }
}
