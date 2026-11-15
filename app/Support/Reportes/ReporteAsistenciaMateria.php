<?php

namespace App\Support\Reportes;

use App\Models\TrxAsistenciaClase;
use Illuminate\Support\Facades\DB;

// Asistencia por materia (pase de lista, trx_asistencia_clase): % por materia y por grupo; alumnos bajo el mínimo (solo con nombres).
// % = (A + R) / (clases registradas − J), igual que TrxAsistenciaClase::scopeResumen.
class ReporteAsistenciaMateria extends Reporte
{
    public const CLAVE = 'asistencia-materia';

    public const TITULO = 'Asistencia por materia';

    public const DESCRIPCION = 'Porcentaje de asistencia por materia y grupo según el pase de lista, y alumnos bajo el mínimo.';

    public const ICONO = 'bi-person-check';

    public function generar(AlcanceDeReporte $alcance, array $opciones): array
    {
        $minimo = TrxAsistenciaClase::minimoPorcentaje();
        $base = fn () => DB::table('trx_asistencia_clase as c')
            ->join('cat_tipo_asistencia as t', 't.id', '=', 'c.cat_tipo_asistencia_id')
            ->join('aca_grupo_asignatura as m', 'm.id', '=', 'c.aca_grupo_asignatura_id')
            ->whereIn('m.aca_grupo_escolar_id', $alcance->grupoIds() ?: [0])
            ->selectRaw('count(*) as clases')
            ->selectRaw("count(*) filter (where t.codigo <> 'J' and t.cuenta_como_asistencia) as asistencias")
            ->selectRaw("count(*) filter (where t.codigo = 'F') as faltas")
            ->selectRaw("count(*) filter (where t.codigo = 'R') as retardos")
            ->selectRaw("count(*) filter (where t.codigo = 'J') as justificadas");
        $porcentaje = fn ($f) => static::porcentaje((int) $f->asistencias, (int) $f->clases - (int) $f->justificadas);

        $consultaMaterias = $base()->join('aca_asignatura as s', 's.id', '=', 'm.aca_asignatura_id')
            ->groupBy('m.id', 'm.aca_grupo_escolar_id', 's.nombre')->addSelect('m.aca_grupo_escolar_id as grupo', 's.nombre as materia')->orderBy('s.nombre');
        if ($alcance->conNombres) {
            $consultaMaterias->join('per_personal as pe', 'pe.id', '=', 'm.docente_id')->join('per_persona as dp', 'dp.id', '=', 'pe.per_persona_id')
                ->groupBy('dp.primer_apellido', 'dp.segundo_apellido', 'dp.nombre')->selectRaw("concat_ws(' ', dp.nombre, dp.primer_apellido, nullif(dp.segundo_apellido, '')) as docente");
        }
        $materias = $consultaMaterias->get()->map(fn ($f) => array_merge([$f->materia, $this->etiquetaGrupo($alcance, (int) $f->grupo)],
            $alcance->conNombres ? [$f->docente] : [], [(int) $f->clases, (int) $f->faltas, (int) $f->retardos, $porcentaje($f)]));
        $columnasMateria = array_merge(['Materia', 'Grupo'], $alcance->conNombres ? ['Docente'] : [], ['Registros', 'Faltas', 'Retardos', 'Asistencia (%)']);
        $iPorcentaje = count($columnasMateria) - 1;

        $grupos = $base()->groupBy('m.aca_grupo_escolar_id')->addSelect('m.aca_grupo_escolar_id as grupo')->get()
            ->sortBy(fn ($f) => $alcance->grupos->search(fn ($g) => $g->id === (int) $f->grupo))
            ->map(fn ($f) => [$this->etiquetaGrupo($alcance, (int) $f->grupo), (int) $f->clases, (int) $f->faltas, (int) $f->retardos, $porcentaje($f)]);
        $totales = $base()->first();

        $secciones = [
            $this->seccion('Por grupo', ['Grupo', 'Registros', 'Faltas', 'Retardos', 'Asistencia (%)'], $grupos, ['porcentajes' => [4], 'grafica' => ['etiqueta' => 0, 'valor' => 4]]),
            $this->seccion('Por materia y grupo', $columnasMateria, $materias, ['porcentajes' => [$iPorcentaje]]),
        ];
        $avisos = ['Un registro es un alumno en una clase. Mínimo: '.TrxAsistenciaClase::formatear($minimo).' (solo aviso).'];
        if ($alcance->conNombres) {
            $bajo = TrxAsistenciaClase::resumen()->bajoMinimo()
                ->whereHas('materia', fn ($materias) => $materias->whereIn('aca_grupo_escolar_id', $alcance->grupoIds() ?: [0]))
                ->with(['inscripcion.alumno.persona', 'materia.asignatura'])->get()
                ->map(fn ($f) => [$f->inscripcion->alumno->persona->nombre_completo, $f->inscripcion->alumno->matricula, $f->materia->asignatura->nombre,
                    $this->etiquetaGrupo($alcance, (int) $f->materia->aca_grupo_escolar_id), (int) $f->faltas, $f->porcentaje])
                ->sortBy([[5, 'asc'], [0, 'asc']]);
            $secciones[] = $this->seccion('Alumnos bajo el mínimo', ['Alumno', 'Matrícula', 'Materia', 'Grupo', 'Faltas', 'Asistencia (%)'], $bajo,
                ['porcentajes' => [5], 'listado' => true, 'vacio' => 'Ningún alumno bajo el mínimo.']);
        } else {
            $avisos[] = 'Tu rol ve solo cifras agregadas: no se listan alumnos ni docentes.';
        }

        return [
            'resumen' => [['Registros', (int) $totales->clases], ['Faltas', (int) $totales->faltas], ['Asistencia', $porcentaje($totales)]],
            'porcentajesResumen' => [2],
            'avisos' => $avisos,
            'secciones' => $secciones,
        ];
    }
}
