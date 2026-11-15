<?php

namespace App\Support\Reportes;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Bajas (aca_baja): temporales y definitivas por grupo y por mes; lista con alumno, fecha, tipo y motivo (solo con nombres).
class ReporteBajas extends Reporte
{
    public const CLAVE = 'bajas';

    public const TITULO = 'Bajas';

    public const DESCRIPCION = 'Bajas temporales y definitivas por grupo y por mes, con su motivo.';

    public const ICONO = 'bi-person-dash';

    public function generar(AlcanceDeReporte $alcance, array $opciones): array
    {
        $base = fn () => $this->inscripciones($alcance)->join('aca_baja as b', 'b.aca_inscripcion_id', '=', 'i.id')
            ->selectRaw("count(*) filter (where b.tipo = 'TEMPORAL') as temporales")->selectRaw("count(*) filter (where b.tipo = 'DEFINITIVA') as definitivas")
            ->selectRaw('count(*) as total');
        $fila = fn ($f, string $etiqueta) => [$etiqueta, (int) $f->temporales, (int) $f->definitivas, (int) $f->total];
        $columnas = fn (string $primera) => [$primera, 'Temporales', 'Definitivas', 'Total'];

        $porGrupo = $base()->groupBy('i.aca_grupo_escolar_id')->addSelect('i.aca_grupo_escolar_id as grupo')->get()
            ->sortBy(fn ($f) => $alcance->grupos->search(fn ($g) => $g->id === (int) $f->grupo))
            ->map(fn ($f) => $fila($f, $this->etiquetaGrupo($alcance, (int) $f->grupo)));
        $porMes = $base()->selectRaw("to_char(b.fecha, 'YYYY-MM') as mes")->groupByRaw("to_char(b.fecha, 'YYYY-MM')")->orderBy('mes')->get()
            ->map(fn ($f) => $fila($f, ucfirst(Carbon::createFromFormat('Y-m-d', $f->mes.'-01')->locale('es')->translatedFormat('F Y'))));
        $totales = $base()->first();

        $secciones = [
            $this->seccion('Por grupo', $columnas('Grupo'), $porGrupo, ['grafica' => ['etiqueta' => 0, 'valor' => 3]]),
            $this->seccion('Por mes', $columnas('Mes'), $porMes),
        ];
        $avisos = [];
        if ($alcance->conNombres) {
            $lista = $this->inscripciones($alcance)->join('aca_baja as b', 'b.aca_inscripcion_id', '=', 'i.id')
                ->join('per_alumno as a', 'a.id', '=', 'i.per_alumno_id')->join('per_persona as p', 'p.id', '=', 'a.per_persona_id')
                ->select('b.fecha', 'b.tipo', 'b.motivo', 'a.matricula', 'i.aca_grupo_escolar_id as grupo')->selectRaw(static::nombreAlumno().' as alumno')
                ->orderBy('b.fecha')->orderBy('alumno')->get()
                ->map(fn ($f) => [$f->alumno, $f->matricula, $this->etiquetaGrupo($alcance, (int) $f->grupo), Carbon::parse($f->fecha)->format('d/m/Y'),
                    $f->tipo === 'TEMPORAL' ? 'Temporal' : 'Definitiva', $f->motivo]);
            $secciones[] = $this->seccion('Bajas del periodo', ['Alumno', 'Matrícula', 'Grupo', 'Fecha', 'Tipo', 'Motivo'], $lista, ['listado' => true, 'vacio' => 'Sin bajas.']);
        } else {
            $avisos[] = 'Tu rol ve solo cifras agregadas: no se listan alumnos.';
        }

        return [
            'resumen' => [['Bajas', (int) $totales->total], ['Temporales', (int) $totales->temporales], ['Definitivas', (int) $totales->definitivas]],
            'avisos' => $avisos,
            'secciones' => $secciones,
        ];
    }
}
