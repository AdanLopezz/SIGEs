<?php

namespace App\Support\Reportes;

use Illuminate\Support\Carbon;

// Asistencia diaria (trx_asistencia_diaria): % por grupo y por semana o día; alumnos con más faltas (solo con nombres).
// % = (presentes + retardos) / (registros − justificadas), como en la asistencia por clase.
class ReporteAsistenciaDiaria extends Reporte
{
    public const CLAVE = 'asistencia-diaria';

    public const TITULO = 'Asistencia diaria';

    public const DESCRIPCION = 'Porcentaje de asistencia por grupo y por semana o día, y alumnos con más faltas.';

    public const ICONO = 'bi-calendar-check';

    public static function opciones(): array
    {
        return ['agrupar' => ['label' => 'Agrupar por', 'opciones' => ['semana' => 'Semana', 'dia' => 'Día'], 'omision' => 'semana']];
    }

    public function generar(AlcanceDeReporte $alcance, array $opciones): array
    {
        $porDia = ($opciones['agrupar'] ?? 'semana') === 'dia';
        $base = fn () => $this->inscripciones($alcance)->join('trx_asistencia_diaria as d', 'd.aca_inscripcion_id', '=', 'i.id')
            ->selectRaw("count(*) filter (where d.estado = 'PRESENTE') as presentes")
            ->selectRaw("count(*) filter (where d.estado = 'RETARDO') as retardos")
            ->selectRaw("count(*) filter (where d.estado = 'FALTA') as faltas")
            ->selectRaw("count(*) filter (where d.estado = 'JUSTIFICADA') as justificadas")
            ->selectRaw('count(*) as total');
        $fila = fn ($f, string $etiqueta) => [$etiqueta, (int) $f->presentes, (int) $f->retardos, (int) $f->faltas, (int) $f->justificadas,
            static::porcentaje((int) $f->presentes + (int) $f->retardos, (int) $f->total - (int) $f->justificadas)];
        $columnas = fn (string $primera) => [$primera, 'Presentes', 'Retardos', 'Faltas', 'Justificadas', 'Asistencia (%)'];
        $conPorcentaje = ['porcentajes' => [5], 'grafica' => ['etiqueta' => 0, 'valor' => 5]];

        $porGrupo = $base()->addSelect('i.aca_grupo_escolar_id as grupo')->groupBy('i.aca_grupo_escolar_id')->get()
            ->sortBy(fn ($f) => $alcance->grupos->search(fn ($g) => $g->id === (int) $f->grupo))
            ->map(fn ($f) => $fila($f, $this->etiquetaGrupo($alcance, (int) $f->grupo)));
        $expresion = $porDia ? 'd.fecha' : "date_trunc('week', d.fecha)::date";
        $porFecha = $base()->selectRaw("{$expresion} as clave")->groupByRaw($expresion)->orderByRaw($expresion)->get()
            ->map(fn ($f) => $fila($f, ($porDia ? '' : 'Semana del ').Carbon::parse($f->clave)->format('d/m/Y')));
        $totales = $base()->first();

        $secciones = [
            $this->seccion('Por grupo', $columnas('Grupo'), $porGrupo, $conPorcentaje),
            $this->seccion($porDia ? 'Por día' : 'Por semana', $columnas($porDia ? 'Día' : 'Semana'), $porFecha, $conPorcentaje),
        ];
        $avisos = ['Los días se registran con la entrada del control de acceso y el cierre del día; las faltas aparecen después del cierre (21:00).'];
        if ($alcance->conNombres) {
            $alumnos = $this->inscripciones($alcance)->join('trx_asistencia_diaria as d', 'd.aca_inscripcion_id', '=', 'i.id')
                ->join('per_alumno as a', 'a.id', '=', 'i.per_alumno_id')->join('per_persona as p', 'p.id', '=', 'a.per_persona_id')
                ->groupBy('i.id', 'a.matricula', 'p.primer_apellido', 'p.segundo_apellido', 'p.nombre', 'i.aca_grupo_escolar_id')
                ->havingRaw("count(*) filter (where d.estado = 'FALTA') > 0")
                ->select('i.aca_grupo_escolar_id as grupo', 'a.matricula')->selectRaw(static::nombreAlumno().' as alumno')
                ->selectRaw("count(*) filter (where d.estado = 'PRESENTE') as presentes")
                ->selectRaw("count(*) filter (where d.estado = 'RETARDO') as retardos")
                ->selectRaw("count(*) filter (where d.estado = 'FALTA') as faltas")
                ->selectRaw("count(*) filter (where d.estado = 'JUSTIFICADA') as justificadas")
                ->selectRaw('count(*) as total')
                ->orderByDesc('faltas')->orderBy('alumno')->get()
                ->map(fn ($f) => [$f->alumno, $f->matricula, $this->etiquetaGrupo($alcance, (int) $f->grupo), (int) $f->faltas, (int) $f->retardos,
                    static::porcentaje((int) $f->presentes + (int) $f->retardos, (int) $f->total - (int) $f->justificadas)]);
            $secciones[] = $this->seccion('Alumnos con más faltas', ['Alumno', 'Matrícula', 'Grupo', 'Faltas', 'Retardos', 'Asistencia (%)'], $alumnos,
                ['porcentajes' => [5], 'listado' => true, 'vacio' => 'Ningún alumno con faltas.']);
        } else {
            $avisos[] = 'Tu rol ve solo cifras agregadas: no se listan alumnos.';
        }

        return [
            'resumen' => [['Registros', (int) $totales->total], ['Faltas', (int) $totales->faltas], ['Retardos', (int) $totales->retardos],
                ['Asistencia', $fila($totales, '')[5]]],
            'porcentajesResumen' => [3],
            'avisos' => $avisos,
            'secciones' => $secciones,
        ];
    }
}
