<?php

namespace App\Support\Reportes;

use App\Models\TrxResultadoAsignatura;
use Illuminate\Support\Facades\DB;

// Reprobación (trx_resultado_asignatura de las materias de los grupos del alcance): % por materia, grupo y docente (solo con nombres)
// y resultados por oportunidad (ordinario, extraordinario, recursamiento).
class ReporteReprobacion extends Reporte
{
    public const CLAVE = 'reprobacion';

    public const TITULO = 'Reprobación';

    public const DESCRIPCION = 'Porcentaje de reprobación por materia, grupo y docente, y resultados por oportunidad.';

    public const ICONO = 'bi-clipboard-x';

    public function generar(AlcanceDeReporte $alcance, array $opciones): array
    {
        $base = fn () => DB::table('trx_resultado_asignatura as r')->join('aca_grupo_asignatura as m', 'm.id', '=', 'r.aca_grupo_asignatura_id')
            ->whereIn('m.aca_grupo_escolar_id', $alcance->grupoIds() ?: [0])
            ->selectRaw('count(*) as total')->selectRaw("count(*) filter (where r.estado = 'REPROBADA') as reprobadas");
        $fila = fn ($f, string $etiqueta) => [$etiqueta, (int) $f->total - (int) $f->reprobadas, (int) $f->reprobadas, (int) $f->total,
            static::porcentaje((int) $f->reprobadas, (int) $f->total)];
        $columnas = fn (string $primera) => [$primera, 'Aprobados', 'Reprobados', 'Resultados', 'Reprobación (%)'];
        $conPorcentaje = ['porcentajes' => [4], 'grafica' => ['etiqueta' => 0, 'valor' => 4]];

        $porMateria = $base()->join('aca_asignatura as s', 's.id', '=', 'm.aca_asignatura_id')->groupBy('s.nombre')->addSelect('s.nombre as etiqueta')->orderBy('s.nombre')
            ->get()->map(fn ($f) => $fila($f, $f->etiqueta));
        $porGrupo = $base()->groupBy('m.aca_grupo_escolar_id')->addSelect('m.aca_grupo_escolar_id as grupo')->get()
            ->sortBy(fn ($f) => $alcance->grupos->search(fn ($g) => $g->id === (int) $f->grupo))
            ->map(fn ($f) => $fila($f, $this->etiquetaGrupo($alcance, (int) $f->grupo)));
        $porOportunidad = $base()->groupBy('r.oportunidad')->addSelect('r.oportunidad as etiqueta')->get()->keyBy('etiqueta');
        $oportunidades = collect(TrxResultadoAsignatura::OPORTUNIDADES)->map(fn ($nombre, $clave) => $porOportunidad->has($clave)
            ? $fila($porOportunidad[$clave], $nombre) : [$nombre, 0, 0, 0, null]);
        $totales = $base()->first();

        $secciones = [
            $this->seccion('Por materia', $columnas('Materia'), $porMateria, $conPorcentaje),
            $this->seccion('Por grupo', $columnas('Grupo'), $porGrupo, ['porcentajes' => [4]]),
            $this->seccion('Por oportunidad', $columnas('Oportunidad'), $oportunidades, ['porcentajes' => [4]]),
        ];
        $avisos = ['Solo resultados cerrados. El recursamiento cuenta en la materia y el grupo donde se recursa.'];
        if ($alcance->conNombres) {
            $porDocente = $base()->join('per_personal as pe', 'pe.id', '=', 'm.docente_id')->join('per_persona as dp', 'dp.id', '=', 'pe.per_persona_id')
                ->groupBy('pe.id', 'dp.primer_apellido', 'dp.segundo_apellido', 'dp.nombre')
                ->selectRaw("concat_ws(' ', dp.nombre, dp.primer_apellido, nullif(dp.segundo_apellido, '')) as etiqueta")->orderBy('etiqueta')
                ->get()->map(fn ($f) => $fila($f, $f->etiqueta));
            array_splice($secciones, 2, 0, [$this->seccion('Por docente', $columnas('Docente'), $porDocente, ['porcentajes' => [4]])]);
        } else {
            $avisos[] = 'Tu rol ve solo cifras agregadas: no se muestran docentes.';
        }

        return [
            'resumen' => [['Resultados', (int) $totales->total], ['Reprobados', (int) $totales->reprobadas], ['Reprobación', static::porcentaje((int) $totales->reprobadas, (int) $totales->total)]],
            'porcentajesResumen' => [2],
            'avisos' => $avisos,
            'secciones' => $secciones,
        ];
    }
}
