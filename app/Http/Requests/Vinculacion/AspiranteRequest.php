<?php

namespace App\Http\Requests\Vinculacion;

use App\Models\PerPersona;
use App\Models\TrxAspirante;
use Illuminate\Validation\Rule;

/**
 * Alta y edición de aspirante (aspirantes.administrar). Si la CURP ya existe, se reutiliza la persona y sus datos no se piden
 * (se conservan). En la edición, los datos de la persona solo se piden si no es compartida (alumno, personal, tutor o con cuenta).
 */
class AspiranteRequest extends VinculacionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('aspirantes.administrar') ?? false;
    }

    public function rules(): array
    {
        $aspirante = $this->route('aspirante');
        $persona = $this->reglasPersona();
        $pidePersona = $aspirante
            ? ! $aspirante->personaCompartida()
            : ! (is_string($this->input('curp')) && PerPersona::where('curp', $this->input('curp'))->exists());

        return [
            'curp' => $aspirante ? ['exclude'] : $this->reglasCurp(function ($atributo, $valor, $fallar) {
                if (PerPersona::where('curp', $valor)->whereHas('alumno')->exists()) {
                    $fallar('Esta persona ya está registrada como alumno.');
                }
            }),
            'aca_carrera_id' => ['bail', 'required', 'integer', Rule::exists('aca_carrera', 'id')
                ->where(fn ($consulta) => $consulta->where('activo', true)->when($aspirante, fn ($consulta) => $consulta->orWhere('id', $aspirante->aca_carrera_id)))],
            'cat_medio_difusion_id' => ['bail', 'required', 'integer', 'exists:cat_medio_difusion,id'],
            'escuela_procedencia' => ['bail', 'required', 'string', 'max:150'],
            'ciclo_ingreso' => ['bail', 'required', 'string', 'size:9', function ($atributo, $valor, $fallar) {
                if (! TrxAspirante::cicloValido($valor)) {
                    $fallar('El ciclo debe tener el formato AAAA-AAAA con años consecutivos (por ejemplo 2027-2028).');
                }
            }],
            'motivo' => $aspirante ? ['required', 'string', 'max:255'] : ['exclude'],
        ] + ($pidePersona ? $persona : array_fill_keys(array_keys($persona), ['exclude']));
    }

    public function messages(): array
    {
        return ['aca_carrera_id.exists' => 'Selecciona una carrera activa.', 'size' => 'El :attribute debe tener :size caracteres.'] + parent::messages();
    }
}
