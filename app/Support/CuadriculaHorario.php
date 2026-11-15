<?php

namespace App\Support;

use App\Models\AcaHorario;
use Illuminate\Support\Collection;

/**
 * Cuadrícula semanal de un horario (solo presentación): filas de `horarios.minutos_por_bloque` minutos desde $desde hasta
 * $hasta (o lo que abarquen las clases) y columnas de lunes a viernes, más el sábado si hay clases ese día o $conSabado.
 * Cada clase ocupa la celda del bloque donde empieza, con rowspan hasta el bloque donde termina. Si dos clases del mismo día
 * comparten un bloque (horas que no coinciden con los bloques), van juntas en una sola celda.
 * $filas[i] = ['inicio' => 'HH:MM', 'fin' => 'HH:MM', 'celdas' => [dia => null (vacía) | 'cubierta' | ['clases' => Collection, 'filas' => n]]].
 */
class CuadriculaHorario
{
    public array $dias = [];

    public array $filas = [];

    public function __construct(public Collection $clases, ?string $desde = null, ?string $hasta = null, bool $conSabado = false)
    {
        $bloque = max(10, (int) config('siges.horarios.minutos_por_bloque', 50));
        $inicios = $clases->map(fn (AcaHorario $clase) => AcaHorario::aMinutos($clase->inicio));
        $fines = $clases->map(fn (AcaHorario $clase) => AcaHorario::aMinutos($clase->fin));
        $desdeMin = $inicios->merge($desde ? [AcaHorario::aMinutos($desde)] : [])->min();
        $hastaMin = $fines->merge($hasta ? [AcaHorario::aMinutos($hasta)] : [])->max();
        $this->dias = array_slice(AcaHorario::DIAS, 0, $conSabado || $clases->contains('dia_semana', 6) ? 6 : 5, true);
        if ($desdeMin === null || $hastaMin === null || $hastaMin <= $desdeMin) {
            return;
        }
        $total = (int) ceil(($hastaMin - $desdeMin) / $bloque);
        for ($i = 0; $i < $total; $i++) {
            $this->filas[$i] = [
                'inicio' => AcaHorario::aHora($desdeMin + $i * $bloque),
                'fin' => AcaHorario::aHora(min($hastaMin, $desdeMin + ($i + 1) * $bloque)),
                'celdas' => array_fill_keys(array_keys($this->dias), null),
            ];
        }
        $acotar = fn (int $fila) => max(0, min($total - 1, $fila));
        foreach ($clases->groupBy('dia_semana') as $dia => $delDia) {
            if (! isset($this->dias[$dia])) {
                continue;
            }
            $grupos = [];
            foreach ($delDia->sortBy(fn (AcaHorario $clase) => $clase->inicio.$clase->fin) as $clase) {
                $primera = $acotar(intdiv(AcaHorario::aMinutos($clase->inicio) - $desdeMin, $bloque));
                $ultima = $acotar(intdiv(AcaHorario::aMinutos($clase->fin) - $desdeMin - 1, $bloque));
                $ultimo = array_key_last($grupos);
                if ($ultimo !== null && $primera <= $grupos[$ultimo]['ultima']) {
                    $grupos[$ultimo]['ultima'] = max($grupos[$ultimo]['ultima'], $ultima);
                    $grupos[$ultimo]['clases'][] = $clase;
                } else {
                    $grupos[] = ['primera' => $primera, 'ultima' => $ultima, 'clases' => [$clase]];
                }
            }
            foreach ($grupos as $grupo) {
                $this->filas[$grupo['primera']]['celdas'][$dia] = ['clases' => collect($grupo['clases']), 'filas' => $grupo['ultima'] - $grupo['primera'] + 1];
                for ($i = $grupo['primera'] + 1; $i <= $grupo['ultima']; $i++) {
                    $this->filas[$i]['celdas'][$dia] = 'cubierta';
                }
            }
        }
    }

    public function vacia(): bool
    {
        return $this->clases->isEmpty();
    }

    // Minutos por semana de cada materia (aca_grupo_asignatura_id => minutos).
    public function minutosPorMateria(): Collection
    {
        return $this->clases->groupBy('aca_grupo_asignatura_id')->map(fn (Collection $clases) => $clases->sum(fn (AcaHorario $clase) => $clase->minutos));
    }
}
