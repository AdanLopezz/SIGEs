<?php

namespace App\Http\Requests\Configuracion;

use App\Models\CatTipoIncidencia;
use Illuminate\Validation\Rule;

class TipoIncidenciaRequest extends ConfiguracionRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        // Casillas: si no vienen marcadas, son false.
        $this->merge(['notifica_tutor' => $this->boolean('notifica_tutor'), 'justifica_falta' => $this->boolean('justifica_falta')]);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['bail', 'required', 'string', 'max:80', Rule::unique('cat_tipo_incidencia', 'nombre')->ignore($this->route('tipo')?->id)],
            'categoria' => ['required', Rule::in(array_keys(CatTipoIncidencia::CATEGORIAS))],
            'nivel_gravedad' => ['required', Rule::in(array_keys(CatTipoIncidencia::GRAVEDADES))],
            'notifica_tutor' => ['boolean'],
            'justifica_falta' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['categoria' => 'categoría', 'nivel_gravedad' => 'gravedad', 'notifica_tutor' => 'notifica al tutor', 'justifica_falta' => 'justifica falta'] + parent::attributes();
    }
}
