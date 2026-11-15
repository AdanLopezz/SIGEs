<?php

namespace App\Http\Requests\Administracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class FiltrarUsuariosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('usuarios.administrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:100'],
            'rol' => ['nullable', 'string', Rule::in(Role::where('guard_name', 'web')->pluck('name'))],
            'acceso' => ['nullable', Rule::in(['con', 'sin'])],
        ];
    }

    public function messages(): array
    {
        return ['buscar.string' => 'La búsqueda debe ser texto.', 'buscar.max' => 'La búsqueda no debe superar 100 caracteres.',
            'rol.string' => 'El rol no es válido.', 'rol.in' => 'Selecciona un rol válido.', 'acceso.in' => 'Selecciona un estado de acceso válido.'];
    }

    public function attributes(): array
    {
        return ['buscar' => 'búsqueda', 'rol' => 'rol', 'acceso' => 'acceso'];
    }
}
