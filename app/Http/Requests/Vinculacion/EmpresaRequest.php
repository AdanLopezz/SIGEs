<?php

namespace App\Http\Requests\Vinculacion;

use App\Models\OrgEmpresa;
use Illuminate\Validation\Rule;

// Alta y edición de empresa (dual.administrar). RFC de 12 (moral) o 13 (física) caracteres en mayúsculas, único.
class EmpresaRequest extends VinculacionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dual.administrar') ?? false;
    }

    public function rules(): array
    {
        $empresa = $this->route('empresa');

        return [
            'nombre' => ['bail', 'required', 'string', 'max:150'],
            'rfc' => ['bail', 'required', 'string', 'regex:'.OrgEmpresa::RFC_REGEX, Rule::unique('org_empresa', 'rfc')->ignore($empresa?->id)],
            'contacto_nombre' => ['bail', 'required', 'string', 'max:150'],
            'contacto_telefono' => ['bail', 'required', 'digits:10'],
            'contacto_correo' => ['bail', 'required', 'email', 'max:150'],
            'motivo' => $empresa ? ['required', 'string', 'max:255'] : ['exclude'],
        ];
    }

    public function messages(): array
    {
        return ['rfc.regex' => 'El RFC debe tener 12 caracteres (persona moral) o 13 (persona física): 3 o 4 letras, fecha AAMMDD y homoclave.',
            'rfc.unique' => 'Ya hay una empresa con este RFC.'] + parent::messages();
    }
}
