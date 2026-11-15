<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\PeriodoConsultaRequest;
use App\Support\PeriodoDeTrabajo;
use Illuminate\Http\Request;

/**
 * Selector de periodo de la barra superior. Solo cambia la sesión del usuario: el periodo activo oficial
 * se cambia únicamente en Configuración → Periodos. Vuelve a la misma pantalla sin sus filtros,
 * porque los filtros (grupo, materia…) eran del periodo anterior.
 */
class PeriodoConsultaController extends Controller
{
    public function update(PeriodoConsultaRequest $solicitud)
    {
        $periodo = (int) $solicitud->validated('periodo');
        if ($periodo === PeriodoDeTrabajo::periodoActivo()?->id) {
            $solicitud->session()->forget(PeriodoDeTrabajo::SESION);
        } else {
            $solicitud->session()->put(PeriodoDeTrabajo::SESION, $periodo);
        }

        return redirect($this->volver($solicitud));
    }

    public function destroy(Request $solicitud)
    {
        $solicitud->session()->forget(PeriodoDeTrabajo::SESION);

        return redirect($this->volver($solicitud));
    }

    // Solo la ruta (sin dominio ni cadena de consulta) de la página anterior de este mismo sitio.
    private function volver(Request $solicitud): string
    {
        $anterior = url()->previous();
        $ruta = parse_url($anterior, PHP_URL_PATH) ?: '/';
        $mismoSitio = parse_url($anterior, PHP_URL_HOST) === null || parse_url($anterior, PHP_URL_HOST) === $solicitud->getHost();

        return $mismoSitio ? url($ruta) : route('dashboard');
    }
}
