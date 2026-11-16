<?php

namespace App\View\Components;

use Illuminate\View\Component;

/**
 * Badge de estado único para todo el sistema (.badge-estado.{exito|aviso|peligro|info|neutro}).
 * Uso: <x-badge-estado estado="PRESENTE" /> o <x-badge-estado tono="exito" texto="Activa" icono="bi-check" />.
 */
class BadgeEstado extends Component
{
    // estado => [tono, etiqueta, icono]
    public const ESTADOS = [
        // Alumno (cat_estado_alumno.nombre)
        'Activo' => ['exito', 'Activo', null],
        'Baja temporal' => ['aviso', 'Baja temporal', null],
        'Baja definitiva' => ['peligro', 'Baja definitiva', null],
        'Egresado' => ['info', 'Egresado', null],
        // Inscripción
        'INSCRITO' => ['exito', 'Inscrito', null],
        'BAJA' => ['peligro', 'Baja', null],
        'CAMBIO_GRUPO' => ['neutro', 'Cambio de grupo', null],
        // Baja
        'TEMPORAL' => ['aviso', 'Temporal', null],
        'DEFINITIVA' => ['peligro', 'Definitiva', null],
        // Asistencia diaria
        'PRESENTE' => ['exito', 'Presente', null],
        'RETARDO' => ['aviso', 'Retardo', null],
        'FALTA' => ['peligro', 'Falta', null],
        'JUSTIFICADA' => ['info', 'Justificada', null],
        'SIN_REGISTRO' => ['neutro', 'Sin registro', null],
        // Permiso de salida
        'PENDIENTE' => ['aviso', 'Pendiente', null],
        'AUTORIZADO' => ['exito', 'Autorizado', null],
        'RECHAZADO' => ['peligro', 'Rechazado', null],
        'CANCELADO' => ['neutro', 'Cancelado', null],
        // Incidencia: estado y gravedad
        'ABIERTA' => ['aviso', 'Abierta', null],
        'EN_SEGUIMIENTO' => ['info', 'En seguimiento', null],
        'CERRADA' => ['neutro', 'Cerrada', null],
        'LEVE' => ['info', 'Leve', null],
        'MODERADA' => ['aviso', 'Moderada', null],
        'GRAVE' => ['peligro', 'Grave', null],
        // Vinculación: convenios (CANCELADO es el de permisos) y aspirantes (INSCRITO es el de inscripciones)
        'VIGENTE' => ['exito', 'Vigente', null],
        'VENCIDO' => ['neutro', 'Vencido', null],
        'VENCE_PRONTO' => ['aviso', 'Vence pronto', 'bi-hourglass-split'],
        'PROSPECTO' => ['info', 'Prospecto', null],
        'REGISTRADO' => ['aviso', 'Registrado', null],
        'DESCARTADO' => ['neutro', 'Descartado', null],
        // Seguimiento del alumno (EN_SEGUIMIENTO es el mismo de Incidencias)
        'ABIERTO' => ['aviso', 'Abierto', null],
        'CERRADO' => ['neutro', 'Cerrado', null],
        'RIESGO_ABANDONO' => ['peligro', 'Riesgo de abandono', 'bi-exclamation-octagon'],
        // Consentimiento
        'OTORGADO' => ['exito', 'Otorgado', 'bi-check-circle-fill'],
        'REVOCADO' => ['peligro', 'Revocado', 'bi-x-circle-fill'],
        'SIN_CONSENTIMIENTO' => ['neutro', 'Pendiente', 'bi-dash-circle'],
        // Tutor
        'TUTOR_ACTIVO' => ['exito', 'Activo', null],
        'TUTOR_INACTIVO' => ['neutro', 'Inactivo', null],
        // Calificaciones (resultado de materia y celdas de la boleta). Evaluación: ABIERTA y CERRADA, como la incidencia.
        'APROBADA' => ['exito', 'Aprobada', null],
        'REPROBADA' => ['peligro', 'Reprobada', null],
        'EN_CAPTURA' => ['neutro', 'En captura', null],
        'SIN_RESULTADO' => ['neutro', 'Sin cerrar', null],
        // Oportunidad del resultado y situación de una materia reprobada (Regularización; PENDIENTE es el de permisos de salida)
        'ORDINARIO' => ['neutro', 'Ordinario', null],
        'EXTRAORDINARIO' => ['aviso', 'Extraordinario', null],
        'RECURSAMIENTO' => ['info', 'Recursamiento', null],
        'EN_RECURSAMIENTO' => ['info', 'En recursamiento', null],
        'REGULARIZADA' => ['exito', 'Regularizada', null],
        'RECURSAMIENTO_REPROBADO' => ['peligro', 'Recursamiento reprobado', null],
        // Bitácora
        'INSERT' => ['exito', 'Alta', null],
        'UPDATE' => ['info', 'Edición', null],
        'DELETE' => ['peligro', 'Eliminación', null],
        'SELECT' => ['neutro', 'Consulta', null],
        // Acceso
        'ENTRADA' => ['exito', 'Entrada', null],
        'SALIDA' => ['info', 'Salida', null],
        'CON_ACCESO' => ['exito', 'Con acceso', null],
        'SIN_ACCESO' => ['neutro', 'Sin acceso', null],
        // Credencial
        'CREDENCIAL_ACTIVA' => ['exito', 'Activa', null],
        'SIN_CREDENCIAL' => ['neutro', 'Sin credencial', null],
        // Cambio de periodo (App\Support\CambioDePeriodo): grupo propuesto, situación del alumno y resultado
        'CREAR' => ['info', 'Crear', null],
        'REUTILIZAR' => ['neutro', 'Reutilizar', null],
        'CONFLICTO' => ['peligro', 'Conflicto', null],
        'PASA' => ['exito', 'Pasa', null],
        'AVISO' => ['aviso', 'Aviso', null],
        'NO_PASA' => ['peligro', 'No pasa', null],
        'ULTIMO_SEMESTRE' => ['info', 'Último semestre', null],
        'YA_INSCRITO' => ['neutro', 'Ya inscrito', null],
        'CREADO' => ['exito', 'Creado', null],
        'REUTILIZADO' => ['neutro', 'Reutilizado', null],
        'REINSCRITO' => ['exito', 'Reinscrito', null],
        'OMITIDO' => ['aviso', 'Omitido', null],
        'EGRESO' => ['info', 'Candidato a egreso', null],
        'EGRESADO' => ['info', 'Egresado', 'bi-mortarboard'],
        'PROXIMO_PERIODO' => ['info', 'Próximo periodo', null],
        // Horarios y pase de lista por clase (CatTipoAsistencia::ESTADOS)
        'CLASE_A' => ['exito', 'Asistencia', null],
        'CLASE_F' => ['peligro', 'Falta', null],
        'CLASE_R' => ['aviso', 'Retardo', null],
        'CLASE_J' => ['info', 'Justificada', null],
        'LISTA_PENDIENTE' => ['aviso', 'Pendiente', null],
        'LISTA_REGISTRADA' => ['exito', 'Registrada', null],
        'BAJO_MINIMO' => ['peligro', 'Bajo el mínimo', 'bi-exclamation-triangle'],
        'SIN_HORARIO' => ['neutro', 'Sin horario', null],
        // Avisos del portal
        'NO_LEIDO' => ['info', 'Nuevo', null],
        'LEIDA' => ['neutro', 'Leído', null],
    ];

    public string $tonoFinal;

    public string $textoFinal;

    public ?string $iconoFinal;

    public function __construct(public ?string $estado = null, public ?string $tono = null, public ?string $texto = null, public ?string $icono = null)
    {
        [$tonoMapa, $textoMapa, $iconoMapa] = self::ESTADOS[$estado] ?? ['neutro', (string) $estado, null];
        $this->tonoFinal = $tono ?? $tonoMapa;
        $this->textoFinal = $texto ?? $textoMapa;
        $this->iconoFinal = $icono ?? $iconoMapa;
    }

    public function render()
    {
        return <<<'BLADE'
            <span {{ $attributes->class(['badge-estado', $tonoFinal]) }}>@if ($iconoFinal)<i class="bi {{ $iconoFinal }}" aria-hidden="true"></i> @endif{{ $textoFinal }}</span>
            BLADE;
    }
}
