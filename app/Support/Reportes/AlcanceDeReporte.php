<?php

namespace App\Support\Reportes;

use App\Models\AcaCarrera;
use App\Models\AcaGrupoEscolar;
use App\Models\AcaPeriodoEscolar;
use App\Models\AcaTurno;
use App\Models\PerAlumno;
use App\Models\User;
use App\Support\PeriodoDeTrabajo;
use Illuminate\Support\Collection;

/**
 * Alcance común de los reportes: periodo (por defecto el de consulta), carrera, turno y grupo, y la visibilidad del usuario:
 *  - PerAlumno::ROLES_VEN_TODOS (Administrador, Director, Subdirector, Control Escolar, Prefectura): todos los grupos, con nombres;
 *  - Planeación y Vinculación (sin ninguno de esos roles): todos los grupos, SOLO en cifras agregadas (sin nombres de alumnos ni de docentes);
 *  - los demás (Orientador…): sus grupos con AcaGrupoEscolar::visiblesEnAsistencia, con nombres.
 */
class AlcanceDeReporte
{
    public const ROLES_SOLO_AGREGADO = ['Planeación', 'Vinculación'];

    // Grupos visibles del periodo (con carrera y turno) antes de filtrar por carrera, turno y grupo: opciones del filtro.
    public Collection $gruposDelPeriodo;

    // Grupos del reporte (visibles y filtrados).
    public Collection $grupos;

    public bool $conNombres;

    private function __construct(public User $usuario, public AcaPeriodoEscolar $periodo, public ?int $carrera, public ?int $turno, public ?int $grupo)
    {
        $soloAgregado = $usuario->hasAnyRole(self::ROLES_SOLO_AGREGADO) && ! $usuario->hasAnyRole(PerAlumno::ROLES_VEN_TODOS);
        $this->conNombres = ! $soloAgregado;
        $consulta = $soloAgregado ? AcaGrupoEscolar::where('aca_periodo_escolar_id', $periodo->id) : AcaGrupoEscolar::visiblesEnAsistencia($usuario, $periodo->id);
        $this->gruposDelPeriodo = $consulta->with(['carrera', 'turno'])
            ->when($carrera, fn ($grupos) => $grupos->where('aca_carrera_id', $carrera))
            ->when($turno, fn ($grupos) => $grupos->where('aca_turno_id', $turno))
            ->ordenados()->get();
        $this->grupos = $grupo ? $this->gruposDelPeriodo->where('id', $grupo)->values() : $this->gruposDelPeriodo;
    }

    // $filtros ya validados (FiltrarReporteRequest). Un periodo no permitido o inexistente cae al de consulta.
    public static function desde(User $usuario, array $filtros): ?self
    {
        $periodo = isset($filtros['periodo']) ? PeriodoDeTrabajo::periodosPermitidos($usuario)->firstWhere('id', (int) $filtros['periodo']) : null;
        $periodo ??= PeriodoDeTrabajo::periodoConsulta();
        if (! $periodo) {
            return null;
        }

        return new static($usuario, $periodo, isset($filtros['carrera']) ? (int) $filtros['carrera'] : null,
            isset($filtros['turno']) ? (int) $filtros['turno'] : null, isset($filtros['grupo']) ? (int) $filtros['grupo'] : null);
    }

    /** @return int[] */
    public function grupoIds(): array
    {
        return $this->grupos->pluck('id')->all();
    }

    // Grupos por id, para poner nombre a las filas agregadas.
    public function grupoPorId(int $id): ?AcaGrupoEscolar
    {
        return $this->grupos->firstWhere('id', $id);
    }

    // Texto de los filtros para el encabezado del CSV y del imprimible.
    public function descripcion(): string
    {
        $partes = ['Periodo: '.$this->periodo->nombre_visible.' ('.$this->periodo->clave.')'];
        if ($this->carrera) {
            $partes[] = 'Carrera: '.AcaCarrera::find($this->carrera)?->nombre;
        }
        if ($this->turno) {
            $partes[] = 'Turno: '.AcaTurno::find($this->turno)?->nombre;
        }
        if ($this->grupo) {
            $partes[] = 'Grupo: '.($this->grupos->first()?->identificador ?? '—');
        }
        if (! $this->conNombres) {
            $partes[] = 'Solo cifras agregadas';
        }

        return implode(' · ', $partes);
    }
}
