<?php

namespace App\Http\Requests\ControlEscolar;

use App\Support\CredencialesDeAcceso;
use Illuminate\Foundation\Http\FormRequest;

// "Crear cuenta" de un tutor: el usuario lo genera el sistema con el nombre del tutor.
// Se conserva el que se vio en el modal si corresponde al tutor y sigue libre; si no, se genera otro.
// Si la persona ya tiene cuenta, el usuario se ignora y solo se le agrega el rol.
class CuentaTutorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('editar', $this->route('alumno')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $persona = $this->route('tutor')->persona;
        $this->merge(['email' => $persona->user
            ? $persona->user->email
            : CredencialesDeAcceso::usuarioPara($persona->nombre, $persona->primer_apellido, $this->input('email'))]);
    }

    public function rules(): array
    {
        return ['email' => ['required', 'string', 'email', 'max:255']];
    }

    public function attributes(): array
    {
        return ['email' => 'usuario'];
    }
}
