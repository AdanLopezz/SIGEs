<?php

namespace App\Http\Requests\Vinculacion;

use App\Models\Archivo;
use App\Models\OrgConvenio;
use Illuminate\Validation\Rule;

// Alta y edición de convenio (dual.administrar). El documento firmado es obligatorio en el alta; al editar, opcional (reemplaza).
class ConvenioRequest extends VinculacionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dual.administrar') ?? false;
    }

    public function rules(): array
    {
        $convenio = $this->route('convenio');

        return [
            'org_empresa_id' => ['bail', 'required', 'integer', 'exists:org_empresa,id'],
            'numero' => ['bail', 'required', 'string', 'max:40', Rule::unique('org_convenio', 'numero')->ignore($convenio?->id)],
            'tipo' => ['bail', 'required', Rule::in(array_keys(OrgConvenio::TIPOS))],
            'fecha_inicio' => ['bail', 'required', 'date_format:Y-m-d'],
            'fecha_fin' => ['bail', 'required', 'date_format:Y-m-d', 'after:fecha_inicio'],
            'documento' => Archivo::reglas(! $convenio),
            'motivo' => $convenio ? ['required', 'string', 'max:255'] : ['exclude'],
        ];
    }

    public function messages(): array
    {
        return ['numero.unique' => 'Ya hay un convenio con este número.'] + parent::messages() + Archivo::mensajes('documento');
    }
}
