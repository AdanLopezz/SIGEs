<?php

namespace App\Policies;

use App\Models\AcaGrupoAsignatura;
use App\Models\User;
use App\Support\PeriodoDeTrabajo;

/**
 * Evaluaciones y calificaciones de una materia de grupo. Cada acción exige su permiso, que la materia sea del usuario
 * (todas para Administrador y Control Escolar, o donde es docente) y que el periodo de la materia lo permita:
 * - ver: cualquier periodo (consulta);
 * - programar, capturar, cerrar evaluaciones y cerrar resultados: solo el periodo activo (OPERAR);
 * - corregir (Control Escolar, con motivo): el activo y los pasados (CORREGIR); nunca uno futuro.
 * Asistencia por clase: pasarLista (periodo activo) y verAsistencia (cualquiera), para el docente de la materia
 * (asistencia_clase.registrar; aquí no cuentan los ROLES_VEN_TODAS) o Control Escolar (asistencia_clase.corregir).
 */
class GrupoAsignaturaPolicy
{
    public const PERMISOS = ['evaluaciones.programar', 'calificaciones.capturar', 'calificaciones.corregir', 'resultados.cerrar'];

    public function ver(User $usuario, AcaGrupoAsignatura $materia): bool
    {
        return $usuario->canAny(self::PERMISOS) && $materia->esVisibleParaCalificar($usuario);
    }

    public function programar(User $usuario, AcaGrupoAsignatura $materia): bool
    {
        return $usuario->can('evaluaciones.programar') && $this->permite($usuario, $materia, PeriodoDeTrabajo::OPERAR);
    }

    public function capturar(User $usuario, AcaGrupoAsignatura $materia): bool
    {
        return $usuario->can('calificaciones.capturar') && $this->permite($usuario, $materia, PeriodoDeTrabajo::OPERAR);
    }

    public function corregir(User $usuario, AcaGrupoAsignatura $materia): bool
    {
        return $usuario->can('calificaciones.corregir') && $this->permite($usuario, $materia, PeriodoDeTrabajo::CORREGIR);
    }

    // El docente que captura o, si falta, quien puede corregir; solo en el periodo activo.
    public function cerrarEvaluacion(User $usuario, AcaGrupoAsignatura $materia): bool
    {
        return ($usuario->can('calificaciones.capturar') || $usuario->can('calificaciones.corregir'))
            && $this->permite($usuario, $materia, PeriodoDeTrabajo::OPERAR);
    }

    public function cerrarResultados(User $usuario, AcaGrupoAsignatura $materia): bool
    {
        return $usuario->can('resultados.cerrar') && $this->permite($usuario, $materia, PeriodoDeTrabajo::OPERAR);
    }

    // Pase de lista: el docente de la materia (asistencia_clase.registrar) o Control Escolar (asistencia_clase.corregir), en el
    // periodo activo. La fecha y el plazo de corrección los decide TrxAsistenciaClase::modoDeRegistro().
    public function pasarLista(User $usuario, AcaGrupoAsignatura $materia): bool
    {
        return $this->veAsistencia($usuario, $materia) && PeriodoDeTrabajo::permite(PeriodoDeTrabajo::OPERAR, $materia->periodoEscolar());
    }

    // Asistencia por clase de la materia (tabla alumnos × fechas): su docente o Control Escolar, en cualquier periodo.
    public function verAsistencia(User $usuario, AcaGrupoAsignatura $materia): bool
    {
        return $this->veAsistencia($usuario, $materia);
    }

    private function veAsistencia(User $usuario, AcaGrupoAsignatura $materia): bool
    {
        return ($usuario->can('asistencia_clase.registrar') && $materia->esDelDocente($usuario)) || $usuario->can('asistencia_clase.corregir');
    }

    private function permite(User $usuario, AcaGrupoAsignatura $materia, string $accion): bool
    {
        return $materia->esVisibleParaCalificar($usuario) && PeriodoDeTrabajo::permite($accion, $materia->periodoEscolar());
    }
}
