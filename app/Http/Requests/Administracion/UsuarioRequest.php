<?php

namespace App\Http\Requests\Administracion;

use App\Models\PerPersona;
use App\Models\User;
use App\Support\CredencialesDeAcceso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if($this->route('usuario')?->esSistema(), 404);

        return $this->user()?->can('usuarios.administrar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['curp', 'email', 'email_personal'] as $campo) {
            if (is_string($this->input($campo))) {
                $valor = trim($this->input($campo));
                $this->merge([$campo => $campo === 'curp' ? mb_strtoupper($valor) : mb_strtolower($valor)]);
            }
        }
        // Alta: el usuario lo genera el sistema. Se conserva el que se vio en el formulario si corresponde al nombre y sigue libre.
        if (! $this->route('usuario') && filled($this->input('nombre')) && filled($this->input('primer_apellido'))
            && is_string($this->input('nombre')) && is_string($this->input('primer_apellido'))) {
            $this->merge(['email' => CredencialesDeAcceso::usuarioPara($this->input('nombre'), $this->input('primer_apellido'), $this->input('email'))]);
        }
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');
        $persona = $usuario?->persona ?? (is_string($this->input('curp')) ? PerPersona::where('curp', $this->input('curp'))->first() : null);
        $personal = $persona?->personal;
        $obligatorio = ! $usuario || $personal;

        return [
            'curp' => $usuario ? ['exclude'] : ['bail', 'required', 'string', 'size:18', 'regex:/^[A-Z]{4}\d{6}[HMX][A-Z]{5}[A-Z0-9]\d$/',
                function ($atributo, $valor, $fallar) {
                    if ($valor === PerPersona::CURP_SISTEMA || PerPersona::where('curp', $valor)->whereHas('user')->exists()) {
                        $fallar('Esta persona ya tiene una cuenta en el sistema.');
                    }
                }],
            'nombre' => ['required', 'string', 'max:100'],
            'primer_apellido' => ['required', 'string', 'max:100'],
            'segundo_apellido' => ['nullable', 'string', 'max:100'],
            'email_personal' => ['required', 'email', 'max:150'],
            'telefono' => ['required', 'digits:10'],
            'calle_numero' => ['required', 'string', 'max:150'],
            'colonia' => ['required', 'string', 'max:100'],
            'municipio' => ['required', 'string', 'max:100'],
            'codigo_postal' => ['required', 'digits:5'],
            'numero_empleado' => [$obligatorio ? 'required' : 'nullable', 'required_with:org_area_id,puesto', 'string', 'max:50', Rule::unique('per_personal', 'numero_empleado')->ignore($personal?->id)],
            'org_area_id' => [$obligatorio ? 'required' : 'nullable', 'required_with:numero_empleado,puesto', 'integer', 'exists:org_area,id'],
            'puesto' => [$obligatorio ? 'required' : 'nullable', 'required_with:numero_empleado,org_area_id', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'distinct', Rule::in(User::rolesAdministrables())],
            // Queda en la bitácora; solo se pide al editar.
            'motivo' => $usuario ? ['required', 'string', 'max:255'] : ['exclude'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validador) {
            $usuario = $this->route('usuario');
            if ($validador->errors()->has('roles') || ! is_array($this->input('roles'))) {
                return;
            }
            if ($usuario?->hasRole('Administrador', 'web') && ! in_array('Administrador', $this->input('roles'), true)) {
                if ($usuario->is($this->user())) {
                    $validador->errors()->add('roles', 'No puedes quitarte el rol de Administrador a ti mismo.');
                } elseif ($usuario->esUltimoAdministrador()) {
                    $validador->errors()->add('roles', 'Debe quedar al menos un administrador en el sistema.');
                }
            }
        }];
    }

    public function attributes(): array
    {
        return ['curp' => 'CURP', 'nombre' => 'nombre', 'primer_apellido' => 'primer apellido', 'segundo_apellido' => 'segundo apellido',
            'email_personal' => 'correo personal', 'telefono' => 'teléfono', 'calle_numero' => 'calle y número', 'colonia' => 'colonia',
            'municipio' => 'municipio', 'codigo_postal' => 'código postal', 'numero_empleado' => 'número de empleado',
            'org_area_id' => 'área', 'puesto' => 'puesto', 'email' => 'usuario', 'roles' => 'roles', 'roles.*' => 'rol',
            'motivo' => 'motivo del cambio'];
    }

    public function messages(): array
    {
        return ['required' => 'El campo :attribute es obligatorio.', 'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no debe superar :max caracteres.', 'email' => 'Escribe un correo válido en :attribute.',
            'digits' => 'El campo :attribute debe tener :digits dígitos.', 'unique' => 'El valor de :attribute ya está registrado.',
            'exists' => 'Selecciona una opción válida para :attribute.', 'integer' => 'Selecciona una opción válida para :attribute.',
            'required_with' => 'Completa todos los datos laborales: número de empleado, área y puesto.',
            'curp.size' => 'La CURP debe tener 18 caracteres.', 'curp.regex' => 'La CURP no tiene un formato válido.',
            'roles.required' => 'Selecciona al menos un rol.', 'roles.min' => 'Selecciona al menos un rol.',
            'roles.array' => 'Selecciona los roles de la lista.', 'roles.*.in' => 'El rol seleccionado no se asigna desde este módulo.',
            'roles.*.distinct' => 'No repitas el mismo rol.'];
    }
}
