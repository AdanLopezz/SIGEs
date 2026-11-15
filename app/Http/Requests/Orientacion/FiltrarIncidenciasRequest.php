<?php

namespace App\Http\Requests\Orientacion;

use App\Models\CatTipoIncidencia;
use App\Models\TrxIncidencia;
use Illuminate\Validation\Rule;

class FiltrarIncidenciasRequest extends OrientacionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(self::PERMISOS_INCIDENCIAS) ?? false;
    }

    public function rules(): array
    {
        return [
            'estado' => ['nullable', Rule::in(array_keys(TrxIncidencia::ESTADOS))],
            'grupo' => ['nullable', 'integer'],
            'categoria' => ['nullable', Rule::in(array_keys(CatTipoIncidencia::CATEGORIAS))],
            'gravedad' => ['nullable', Rule::in(array_keys(CatTipoIncidencia::GRAVEDADES))],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'alumno' => ['nullable', 'integer'],
        ];
    }
}
