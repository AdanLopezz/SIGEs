<?php

namespace App\Http\Requests\Prefectura;

use App\Models\AcaGrupoEscolar;

class JustificarAsistenciaRequest extends PrefecturaRequest
{
    // incidencias.cerrar (Orientador) o alumnos.editar (Control Escolar), y el grupo debe ser visible.
    public function authorize(): bool
    {
        $usuario = $this->user();
        $grupo = $this->route('asistencia')->inscripcion->aca_grupo_escolar_id;

        return $usuario !== null && $usuario->canAny(self::PERMISOS_JUSTIFICAR)
            && AcaGrupoEscolar::visiblesEnAsistencia($usuario)->whereKey($grupo)->exists();
    }

    public function rules(): array
    {
        return ['motivo' => ['bail', 'required', 'string', 'max:255']];
    }

    public function attributes(): array
    {
        return ['motivo' => 'motivo de la justificación'] + parent::attributes();
    }
}
