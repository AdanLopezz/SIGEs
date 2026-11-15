<?php

namespace App\Support\Reportes;

use Illuminate\Support\Collection;

// Matrícula: INSCRITO por carrera, semestre, turno y grupo. Sexo del carácter 11 de la CURP (H / M; X u otro = No identificado).
class ReporteMatricula extends Reporte
{
    public const CLAVE = 'matricula';

    public const TITULO = 'Matrícula';

    public const DESCRIPCION = 'Alumnos inscritos por carrera, semestre, turno y grupo, con hombres y mujeres según la CURP.';

    public const ICONO = 'bi-people';

    public function generar(AlcanceDeReporte $alcance, array $opciones): array
    {
        $porGrupo = $this->inscripciones($alcance)->join('per_alumno as a', 'a.id', '=', 'i.per_alumno_id')->join('per_persona as p', 'p.id', '=', 'a.per_persona_id')
            ->where('i.estado', 'INSCRITO')->groupBy('i.aca_grupo_escolar_id')
            ->select('i.aca_grupo_escolar_id as grupo')
            ->selectRaw("count(*) filter (where upper(substr(p.curp, 11, 1)) = 'H') as hombres")
            ->selectRaw("count(*) filter (where upper(substr(p.curp, 11, 1)) = 'M') as mujeres")
            ->selectRaw('count(*) as total')
            ->get()->map(function ($fila) use ($alcance) {
                $grupo = $alcance->grupoPorId((int) $fila->grupo);

                return ['grupo' => $grupo?->identificador ?? '#'.$fila->grupo, 'carrera' => $grupo?->carrera?->nombre ?? '—', 'turno' => $grupo?->turno?->nombre ?? '—',
                    'semestre' => $grupo ? $grupo->semestre.'.º semestre' : '—', 'orden' => $grupo ? $alcance->grupos->search(fn ($g) => $g->id === $grupo->id) : 0,
                    'hombres' => (int) $fila->hombres, 'mujeres' => (int) $fila->mujeres, 'total' => (int) $fila->total];
            })->sortBy('orden')->values();
        $campos = ['hombres', 'mujeres', 'total'];
        $columnas = fn (string $primera) => [$primera, 'Hombres', 'Mujeres', 'No identificado', 'Total'];
        $filas = fn (Collection $sumas) => $sumas->map(fn (array $s, $etiqueta) => [$etiqueta, $s['hombres'], $s['mujeres'], $s['total'] - $s['hombres'] - $s['mujeres'], $s['total']]);
        $grafica = ['grafica' => ['etiqueta' => 0, 'valor' => 4]];
        $total = $porGrupo->sum('total');
        $hombres = $porGrupo->sum('hombres');
        $mujeres = $porGrupo->sum('mujeres');

        return [
            'resumen' => [['Inscritos', $total], ['Hombres', $hombres], ['Mujeres', $mujeres], ['No identificado', $total - $hombres - $mujeres], ['Grupos', $porGrupo->count()]],
            'avisos' => ['Hombres y mujeres se derivan del carácter 11 de la CURP; "No identificado" incluye la X y las CURP que no lo indican.'],
            'secciones' => [
                $this->seccion('Por carrera', $columnas('Carrera'), $filas(static::sumarPor($porGrupo, fn ($f) => $f['carrera'], $campos)), $grafica),
                $this->seccion('Por semestre', $columnas('Semestre'), $filas(static::sumarPor($porGrupo->sortBy('semestre'), fn ($f) => $f['semestre'], $campos))),
                $this->seccion('Por turno', $columnas('Turno'), $filas(static::sumarPor($porGrupo, fn ($f) => $f['turno'], $campos))),
                $this->seccion('Por grupo', $columnas('Grupo'), $filas(static::sumarPor($porGrupo, fn ($f) => $f['grupo'], $campos))),
            ],
        ];
    }
}
