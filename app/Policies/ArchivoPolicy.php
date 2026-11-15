<?php

namespace App\Policies;

use App\Models\Archivo;
use App\Models\OrgConvenio;
use App\Models\PerAlumno;
use App\Models\PerConsentimiento;
use App\Models\TrxIncidencia;
use App\Models\User;

class ArchivoPolicy
{
    // Regla base: Administrador o quien lo subió. Cada módulo agrega aquí su regla
    // para que vea el archivo quien pueda ver el registro al que pertenece.
    public function ver(User $usuario, Archivo $archivo): bool
    {
        return $usuario->hasRole('Administrador', 'web')
            || $archivo->subido_por_user_id === $usuario->id
            || $this->esExpedienteVisible($usuario, $archivo)
            || $this->esConsentimientoVisible($usuario, $archivo)
            || $this->esDocumentoDeIncidenciaVisible($usuario, $archivo)
            || $this->esConvenioVisible($usuario, $archivo);
    }

    // Vinculación: el convenio firmado lo ve quien administra o revisa convenios.
    private function esConvenioVisible(User $usuario, Archivo $archivo): bool
    {
        return $usuario->canAny(['dual.administrar', 'dual.revisar']) && OrgConvenio::where('archivo_id', $archivo->id)->exists();
    }

    // Incidencias: el documento (receta, incapacidad, acta o citatorio) lo ve quien puede ver la incidencia.
    private function esDocumentoDeIncidenciaVisible(User $usuario, Archivo $archivo): bool
    {
        return $usuario->canAny(['incidencias.registrar', 'incidencias.cerrar'])
            && TrxIncidencia::where('archivo_id', $archivo->id)->visiblesPara($usuario)->exists();
    }

    // Consentimientos: el documento firmado lo ve quien puede ver al alumno de ese tutor.
    private function esConsentimientoVisible(User $usuario, Archivo $archivo): bool
    {
        return $usuario->can('alumnos.ver')
            && PerConsentimiento::where('archivo_id', $archivo->id)
                ->whereHas('tutor.alumno', fn ($alumnos) => $alumnos->visiblesPara($usuario))->exists();
    }

    // Control Escolar: el expediente del alumno lo ve quien puede ver al alumno.
    private function esExpedienteVisible(User $usuario, Archivo $archivo): bool
    {
        return $usuario->can('alumnos.ver')
            && PerAlumno::where('expediente_archivo_id', $archivo->id)->visiblesPara($usuario)->exists();
    }
}
