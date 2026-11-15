<?php

namespace App\Http\Requests\Panel;

use App\Support\PeriodoDeTrabajo;
use Illuminate\Foundation\Http\FormRequest;

// Elegir el periodo de consulta: solo uno de los que el usuario puede ver (el portal, solo los de su alumno o hijo).
class PeriodoConsultaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $periodo = $this->input('periodo');

        return $this->user() !== null && is_numeric($periodo)
            && PeriodoDeTrabajo::periodosPermitidos($this->user())->contains('id', (int) $periodo);
    }

    public function rules(): array
    {
        return ['periodo' => ['required', 'integer']];
    }
}
