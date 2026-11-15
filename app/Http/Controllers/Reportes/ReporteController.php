<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\FiltrarReporteRequest;
use App\Models\AcaCarrera;
use App\Models\AcaTurno;
use App\Models\SysConfiguracion;
use App\Support\Paginacion;
use App\Support\PeriodoDeTrabajo;
use App\Support\Reportes\AlcanceDeReporte;
use App\Support\Reportes\Catalogo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Reportes (reportes.ver). Cada reporte (App\Support\Reportes\*) decide quién lo ve (puedeVer) y arma sus tablas sobre AlcanceDeReporte,
 * que aplica la visibilidad de cada usuario. Pantalla, "Descargar CSV" (UTF-8 con BOM) e "Imprimir" (layouts/impresion) usan los mismos datos.
 */
class ReporteController extends Controller
{
    public function index(Request $solicitud)
    {
        $reportes = Catalogo::para($solicitud->user());

        return view('reportes.index', compact('reportes'));
    }

    public function show(FiltrarReporteRequest $solicitud, string $reporte)
    {
        [$clase, $alcance, $datos, $filtros] = $this->generar($solicitud, $reporte);
        // En pantalla se pagina la lista de alumnos (si la hay); el CSV y el imprimible llevan todo.
        foreach ($datos['secciones'] as $indice => $seccion) {
            if ($seccion['listado']) {
                $datos['secciones'][$indice]['filas'] = Paginacion::paginarColeccion($seccion['filas'], $solicitud);
                break;
            }
        }
        $carreras = AcaCarrera::ordenados()->pluck('nombre', 'id');
        $turnos = AcaTurno::ordenados()->pluck('nombre', 'id');
        $periodos = PeriodoDeTrabajo::periodosPermitidos($solicitud->user())->mapWithKeys(fn ($periodo) => [$periodo->id => $periodo->nombre_visible.' · '.$periodo->clave]);

        return view('reportes.show', compact('clase', 'alcance', 'datos', 'filtros', 'carreras', 'turnos', 'periodos'));
    }

    public function csv(FiltrarReporteRequest $solicitud, string $reporte)
    {
        [$clase, $alcance, $datos] = $this->generar($solicitud, $reporte);
        $archivo = 'reporte-'.$clase::CLAVE.'-'.Str::slug($alcance->periodo->clave).'.csv';
        $flujo = Catalogo::csv($clase::TITULO, $alcance, $datos);

        return response()->streamDownload(function () use ($flujo) {
            fpassthru($flujo);
            fclose($flujo);
        }, $archivo, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    public function imprimir(FiltrarReporteRequest $solicitud, string $reporte)
    {
        [$clase, $alcance, $datos, $filtros] = $this->generar($solicitud, $reporte);
        $plantel = SysConfiguracion::actual();

        return view('reportes.imprimir', compact('clase', 'alcance', 'datos', 'filtros', 'plantel'));
    }

    private function generar(FiltrarReporteRequest $solicitud, string $reporte): array
    {
        $clase = Catalogo::clase($reporte);
        $filtros = $solicitud->validated();
        $alcance = AlcanceDeReporte::desde($solicitud->user(), $filtros);
        abort_if($alcance === null, 404, 'No hay un periodo escolar.');
        $opciones = collect($clase::opciones())->map(fn (array $opcion, string $nombre) => $filtros[$nombre] ?? $opcion['omision'])->all();

        return [$clase, $alcance, (new $clase)->generar($alcance, $opciones), $filtros + $opciones];
    }
}
