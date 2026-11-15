<?php

namespace App\Http\Requests\Reportes;

use App\Support\Reportes\Catalogo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Filtros comunes (periodo, carrera, turno, grupo) y los propios del reporte. El periodo se limita a los permitidos en AlcanceDeReporte.
class FiltrarReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $clase = Catalogo::clase((string) $this->route('reporte'));

        return $clase !== null && $this->user() !== null && $clase::puedeVer($this->user());
    }

    public function rules(): array
    {
        $clase = Catalogo::clase((string) $this->route('reporte'));
        $propias = collect($clase ? $clase::opciones() : [])->map(fn (array $opcion) => ['nullable', Rule::in(array_keys($opcion['opciones']))])->all();

        return [
            'periodo' => ['nullable', 'integer'],
            'carrera' => ['nullable', 'integer'],
            'turno' => ['nullable', 'integer'],
            'grupo' => ['nullable', 'integer'],
        ] + $propias;
    }

    public function attributes(): array
    {
        return ['periodo' => 'periodo', 'carrera' => 'carrera', 'turno' => 'turno', 'grupo' => 'grupo', 'agrupar' => 'agrupar por'];
    }

    public function messages(): array
    {
        return ['integer' => 'Selecciona una opción válida para :attribute.', 'in' => 'Selecciona una opción válida para :attribute.'];
    }
}
