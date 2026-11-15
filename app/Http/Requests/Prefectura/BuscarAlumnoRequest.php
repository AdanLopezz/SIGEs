<?php

namespace App\Http\Requests\Prefectura;

// Búsqueda de alumno en Control de acceso y en Permisos de salida.
class BuscarAlumnoRequest extends PrefecturaRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['acceso.registrar', 'permisos_salida.administrar']) ?? false;
    }

    public function rules(): array
    {
        return ['buscar' => ['nullable', 'string', 'max:100'], 'alumno' => ['nullable', 'integer']];
    }
}
