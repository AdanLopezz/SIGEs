<?php

namespace App\Support\Reportes;

use App\Models\CatTipoIncidencia;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Incidencias (sin descripción ni resolución): por tipo, categoría, gravedad y grupo; abiertas contra cerradas.
// Solo para quien ya ve incidencias (incidencias.registrar o incidencias.cerrar); Control Escolar no lo ve.
class ReporteIncidencias extends Reporte
{
    public const CLAVE = 'incidencias';

    public const TITULO = 'Incidencias';

    public const DESCRIPCION = 'Incidencias por tipo, categoría, gravedad y grupo, abiertas y cerradas.';

    public const ICONO = 'bi-exclamation-triangle';

    public static function puedeVer(User $usuario): bool
    {
        return parent::puedeVer($usuario) && $usuario->canAny(['incidencias.registrar', 'incidencias.cerrar']);
    }

    public function generar(AlcanceDeReporte $alcance, array $opciones): array
    {
        // Solo columnas de conteo: nunca descripcion ni resolucion.
        $base = fn () => DB::table('trx_incidencia as n')->join('cat_tipo_incidencia as t', 't.id', '=', 'n.cat_tipo_incidencia_id')
            ->whereIn('n.aca_inscripcion_id', $this->inscripciones($alcance)->select('i.id'))
            ->selectRaw("count(*) filter (where n.estado <> 'CERRADA') as abiertas")->selectRaw("count(*) filter (where n.estado = 'CERRADA') as cerradas")
            ->selectRaw('count(*) as total');
        $fila = fn ($f, string $etiqueta) => [$etiqueta, (int) $f->abiertas, (int) $f->cerradas, (int) $f->total];
        $columnas = fn (string $primera) => [$primera, 'Abiertas', 'Cerradas', 'Total'];
        $porCampo = fn (string $campo, array $nombres) => $base()->groupBy("t.{$campo}")->addSelect("t.{$campo} as etiqueta")->get()->keyBy('etiqueta')
            ->pipe(fn ($filas) => collect($nombres)->filter(fn ($nombre, $clave) => $filas->has($clave))->map(fn ($nombre, $clave) => $fila($filas[$clave], $nombre)));

        $porTipo = $base()->groupBy('t.nombre')->addSelect('t.nombre as etiqueta')->orderByDesc('total')->orderBy('t.nombre')->get()->map(fn ($f) => $fila($f, $f->etiqueta));
        $porGrupo = $base()->join('aca_inscripcion as gi', 'gi.id', '=', 'n.aca_inscripcion_id')->groupBy('gi.aca_grupo_escolar_id')
            ->addSelect('gi.aca_grupo_escolar_id as grupo')->get()
            ->sortBy(fn ($f) => $alcance->grupos->search(fn ($g) => $g->id === (int) $f->grupo))
            ->map(fn ($f) => $fila($f, $this->etiquetaGrupo($alcance, (int) $f->grupo)));
        $totales = $base()->first();
        $grafica = ['grafica' => ['etiqueta' => 0, 'valor' => 3]];

        return [
            'resumen' => [['Incidencias', (int) $totales->total], ['Abiertas', (int) $totales->abiertas], ['Cerradas', (int) $totales->cerradas]],
            'avisos' => ['Abiertas incluye las que están en seguimiento. No se incluyen descripciones ni resoluciones.'],
            'secciones' => [
                $this->seccion('Por tipo', $columnas('Tipo'), $porTipo, $grafica),
                $this->seccion('Por categoría', $columnas('Categoría'), $porCampo('categoria', CatTipoIncidencia::CATEGORIAS)),
                $this->seccion('Por gravedad', $columnas('Gravedad'), $porCampo('nivel_gravedad', CatTipoIncidencia::GRAVEDADES)),
                $this->seccion('Por grupo', $columnas('Grupo'), $porGrupo),
            ],
        ];
    }
}
