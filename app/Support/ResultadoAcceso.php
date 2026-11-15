<?php

namespace App\Support;

use App\Models\PerAlumno;
use App\Models\TrxAsistenciaDiaria;
use App\Models\TrxRegistroAcceso;

// Resultado de una lectura en el control de acceso, listo para mostrarse.
class ResultadoAcceso
{
    public function __construct(
        public bool $valido,
        public ?PerAlumno $alumno = null,
        public ?TrxRegistroAcceso $registro = null,
        public ?string $motivo = null,
        public bool $repetida = false,
        public ?string $alerta = null,
        public ?string $aviso = null,
        public ?string $asistencia = null,
    ) {}

    public static function rechazo(string $motivo, ?PerAlumno $alumno = null): self
    {
        return new self(false, $alumno, motivo: $motivo);
    }

    // Datos planos para la vista (también viaja en la sesión en el registro manual).
    public function aArreglo(): array
    {
        $persona = $this->alumno?->persona;
        $grupo = $this->alumno?->inscripcionActual?->grupo;

        return [
            'valido' => $this->valido,
            'iniciales' => $persona?->iniciales ?? '?',
            'nombre' => $persona?->nombre_completo ?? 'Credencial no reconocida',
            'matricula' => $this->alumno?->matricula,
            'grupo' => $grupo?->nombre_completo,
            'tipo' => $this->registro?->tipo_movimiento,
            'hora' => $this->registro?->fecha_hora?->format('H:i'),
            'motivo' => $this->motivo,
            'repetida' => $this->repetida,
            'alerta' => $this->alerta,
            'aviso' => $this->aviso,
            'asistencia' => $this->asistencia ? TrxAsistenciaDiaria::ESTADOS[$this->asistencia][0] : null,
        ];
    }
}
