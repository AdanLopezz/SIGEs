<?php

namespace App\Http\Requests\Prefectura;

use App\Models\TrxPermisoConfirmacion;
use Illuminate\Validation\Rule;

class ConfirmarPermisoRequest extends PrefecturaRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('permisos_salida.administrar') && $this->route('permiso')->esVisiblePara($this->user())) ?? false;
    }

    public function rules(): array
    {
        return [
            // Solo los tutores del alumno del permiso.
            'per_tutor_alumno_id' => ['bail', 'required', 'integer', Rule::exists('per_tutor_alumno', 'id')->where('activo', true)
                ->where('per_alumno_id', $this->route('permiso')->inscripcion->per_alumno_id)],
            'decision' => ['required', Rule::in(array_keys(TrxPermisoConfirmacion::DECISIONES))],
            'metodo' => ['required', Rule::in(array_keys(TrxPermisoConfirmacion::METODOS))],
            'evidencia' => ['bail', 'required', 'string', 'max:255'],
        ];
    }
}
