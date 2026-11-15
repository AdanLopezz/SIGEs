<?php

namespace App\Http\Requests\Cuentas;

use Illuminate\Foundation\Http\FormRequest;

class GenerarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['usuarios.administrar', 'alumnos.editar']) ?? false;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'primer_apellido' => ['required', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return ['nombre' => 'nombre', 'primer_apellido' => 'primer apellido'];
    }
}
