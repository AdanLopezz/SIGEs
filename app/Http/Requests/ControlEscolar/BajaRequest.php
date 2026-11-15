<?php

namespace App\Http\Requests\ControlEscolar;

use App\Models\AcaBaja;
use App\Models\PerAlumno;
use Illuminate\Validation\Rule;

class BajaRequest extends ControlEscolarRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('bajas.registrar') && $this->user()->can('ver', $this->route('alumno'))) ?? false;
    }

    public function rules(): array
    {
        $inscripcion = $this->route('alumno')->inscripcionActual()->with('grupo.periodo')->first();
        $fecha = ['bail', 'required', 'date_format:Y-m-d'];
        // Sin inscripción vigente el modelo rechaza la baja con su propio mensaje.
        if ($inscripcion) {
            [$minima, $maxima] = PerAlumno::limitesFechaBaja($inscripcion);
            $fecha[] = 'after_or_equal:'.$minima->toDateString();
            $fecha[] = 'before_or_equal:'.$maxima->toDateString();
        }

        return [
            'tipo' => ['required', Rule::in(array_keys(AcaBaja::TIPOS))],
            'fecha' => $fecha,
            'motivo' => ['bail', 'required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['tipo' => 'tipo de baja', 'fecha' => 'fecha de la baja', 'motivo' => 'motivo de la baja'] + parent::attributes();
    }

    public function messages(): array
    {
        return ['fecha.after_or_equal' => 'La fecha de la baja no puede ser anterior al inicio del periodo ni a la inscripción (:date).',
            'fecha.before_or_equal' => 'La fecha de la baja no puede ser futura ni posterior al fin del periodo (:date).',
            'motivo.min' => 'Describe el motivo de la baja con al menos :min caracteres.'] + parent::messages();
    }
}
