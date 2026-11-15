<?php

namespace App\Http\Requests\Orientacion;

// Buscador de alumno del formulario de alta (GET ?buscar=…&alumno=…).
class BuscarAlumnoIncidenciaRequest extends OrientacionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('incidencias.registrar') ?? false;
    }

    public function rules(): array
    {
        return ['buscar' => ['nullable', 'string', 'max:100'], 'alumno' => ['nullable', 'integer']];
    }
}
