<?php

namespace App\Http\Requests\ControlEscolar;

use App\Models\AcaPeriodoEscolar;
use App\Models\Archivo;
use App\Models\PerAlumnoSalud;
use App\Models\PerPersona;
use App\Models\PerTutorAlumno;
use App\Models\TrxAspirante;
use App\Support\CredencialesDeAcceso;
use Illuminate\Validation\Rule;

class AlumnoRequest extends ControlEscolarRequest
{
    public function authorize(): bool
    {
        $alumno = $this->route('alumno');

        // El alta exige también salud: NSS y tipo de sangre son obligatorios en la BD.
        return $alumno
            ? $this->user()?->can('editar', $alumno) ?? false
            : ($this->user()?->can('alumnos.editar') && $this->user()->can('alumnos.salud.ver'));
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge(['tutor_mismo_domicilio' => $this->boolean('tutor_mismo_domicilio')]);
        // Conversión de un aspirante: la CURP es siempre la de su persona (no se duplica la persona).
        if (! $this->route('alumno') && is_numeric($this->input('trx_aspirante_id'))) {
            $curp = TrxAspirante::with('persona')->find((int) $this->input('trx_aspirante_id'))?->persona?->curp;
            if ($curp) {
                $this->merge(['curp' => $curp]);
            }
        }
        // Alta: el correo institucional (y futuro usuario) lo genera el sistema. Se conserva el del formulario si corresponde
        // al nombre y sigue libre; si no, se genera otro. En la edición se conserva el que tenga.
        if (! $this->route('alumno') && is_string($this->input('nombre')) && is_string($this->input('primer_apellido'))
            && filled($this->input('nombre')) && filled($this->input('primer_apellido'))) {
            $this->merge(['email_institucional' => CredencialesDeAcceso::usuarioPara($this->input('nombre'), $this->input('primer_apellido'), $this->input('email_institucional'))]);
        }
    }

    public function rules(): array
    {
        $alumno = $this->route('alumno');
        $persona = $alumno?->persona ?? (is_string($this->input('curp')) ? PerPersona::where('curp', $this->input('curp'))->first() : null);
        $conSalud = $alumno ? $this->user()->can('verSalud', $alumno) : true;
        $carreraBloqueada = $alumno?->inscripcionActual()->exists() ?? false;
        $carrera = $carreraBloqueada ? $alumno->aca_carrera_id : $this->input('aca_carrera_id');

        $reglas = [
            'curp' => $alumno ? ['exclude'] : $this->reglasCurp(function ($atributo, $valor, $fallar) {
                if (PerPersona::where('curp', $valor)->whereHas('alumno')->exists()) {
                    $fallar('Esta persona ya está registrada como alumno.');
                }
            }),
            ...$this->reglasPersona(),
            'matricula' => ['bail', 'required', 'string', 'max:30', Rule::unique('per_alumno', 'matricula')->ignore($alumno?->id)],
            // También único en users, para que después se pueda crear la cuenta con este correo.
            'email_institucional' => ['bail', 'required', 'email', 'max:150', Rule::unique('per_alumno', 'email_institucional')->ignore($alumno?->id),
                Rule::unique('users', 'email')->ignore($persona?->user?->id)],
            'aca_carrera_id' => $carreraBloqueada ? ['exclude'] : ['bail', 'required', 'integer', Rule::exists('aca_carrera', 'id')
                ->where(fn ($consulta) => $consulta->where('activo', true)->when($alumno, fn ($consulta) => $consulta->orWhere('id', $alumno->aca_carrera_id)))],
            'fecha_ingreso' => ['bail', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'aca_grupo_escolar_id' => $alumno ? ['exclude'] : ['bail', 'required', 'integer', Rule::exists('aca_grupo_escolar', 'id')
                ->where('aca_carrera_id', is_numeric($carrera) ? (int) $carrera : 0)
                ->where('aca_periodo_escolar_id', AcaPeriodoEscolar::activo()?->id ?? 0)],
            'nss' => $conSalud ? ['bail', 'required', 'digits:11', Rule::unique('per_alumno_salud', 'nss')->ignore($alumno?->salud?->id)] : ['exclude'],
            'tipo_sangre' => $conSalud ? ['bail', 'required', Rule::in(PerAlumnoSalud::TIPOS_SANGRE)] : ['exclude'],
            'alergias' => $conSalud ? ['nullable', 'string', 'max:1000'] : ['exclude'],
            'condiciones_medicas' => $conSalud ? ['nullable', 'string', 'max:1000'] : ['exclude'],
            'expediente' => Archivo::reglas(! $alumno),
            // Queda en la bitácora; solo se pide al editar.
            'motivo' => $alumno ? ['required', 'string', 'max:255'] : ['exclude'],
            // Solo en el alta: aspirante REGISTRADO que se convierte en alumno (TrxAspirante::convertir).
            'trx_aspirante_id' => $alumno ? ['exclude'] : ['nullable', 'integer', Rule::exists('trx_aspirante', 'id')->where('estado', TrxAspirante::REGISTRADO)],
        ];

        return $alumno ? $reglas : $reglas + $this->reglasTutor();
    }

    // Tutor principal (solo en el alta). Si la CURP ya existe se reutiliza la persona y sus datos no se piden.
    private function reglasTutor(): array
    {
        $existe = is_string($this->input('tutor_curp')) && PerPersona::where('curp', $this->input('tutor_curp'))->exists();
        $persona = $this->reglasPersona('tutor_', true);

        return [
            'tutor_curp' => [...$this->reglasCurp(), 'different:curp'],
            'tutor_parentesco' => ['required', Rule::in(array_keys(PerTutorAlumno::PARENTESCOS))],
            'tutor_mismo_domicilio' => ['boolean'],
        ] + ($existe ? array_fill_keys(array_keys($persona), ['exclude']) : $persona);
    }

    public function messages(): array
    {
        return parent::messages() + Archivo::mensajes('expediente');
    }
}
