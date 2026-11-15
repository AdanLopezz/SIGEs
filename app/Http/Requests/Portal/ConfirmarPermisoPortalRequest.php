<?php

namespace App\Http\Requests\Portal;

use App\Models\TrxPermisoConfirmacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

// El padre autoriza o rechaza un permiso pendiente de su hijo, antes de la hora de salida (PortalPolicy::confirmarPermiso).
class ConfirmarPermisoPortalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('portal-confirmar-permiso', $this->route('permiso'));
    }

    public function rules(): array
    {
        return ['decision' => ['required', Rule::in(array_keys(TrxPermisoConfirmacion::DECISIONES))]];
    }

    public function attributes(): array
    {
        return ['decision' => 'decisión'];
    }

    public function messages(): array
    {
        return ['required' => 'Elige si autorizas o rechazas el permiso.', 'in' => 'Elige si autorizas o rechazas el permiso.'];
    }
}
