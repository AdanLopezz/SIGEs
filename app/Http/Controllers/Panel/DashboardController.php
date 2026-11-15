<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ComNotificacion;
use App\Models\PerAlumno;
use App\Models\SysConfiguracion;
use App\Policies\PortalPolicy;
use App\Support\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __invoke(Request $solicitud)
    {
        $usuario = $solicitud->user()->loadMissing(['persona', 'roles']);

        // Quien solo tiene Alumno o Padre de familia ve el resumen del portal; las cuentas mixtas conservan su inicio.
        if ($usuario->getRoleNames()->diff(PortalPolicy::ROLES)->isEmpty()) {
            return $this->inicioDelPortal($usuario);
        }

        $modulos = Menu::modulosPara($usuario);
        $configuracion = SysConfiguracion::actual();
        $plantelConfigurado = $configuracion && filled($configuracion->clave_cct) && filled($configuracion->nombre);

        return view('panel.dashboard', compact('usuario', 'modulos', 'plantelConfigurado'));
    }

    private function inicioDelPortal($usuario)
    {
        $propio = $usuario->hasRole(PortalPolicy::ALUMNO) ? PerAlumno::deLaCuenta($usuario) : null;
        $propio = $propio && Gate::allows('portal-propio', $propio) ? $propio : null;
        $hijos = $usuario->hasRole(PortalPolicy::PADRE)
            ? PerAlumno::with(['persona', 'inscripcionActual.grupo'])->deTutor($usuario->per_persona_id)->ordenados()->get()
            : collect();
        // [alumno, resumen, contexto] por cada alumno que la cuenta puede ver.
        $tarjetas = collect($propio ? [[$propio->loadMissing(['persona', 'inscripcionActual.grupo']), 'mi-escuela']] : [])
            ->concat($hijos->map(fn (PerAlumno $hijo) => [$hijo, 'mis-hijos']))
            ->map(fn (array $tarjeta) => ['alumno' => $tarjeta[0], 'contexto' => $tarjeta[1], 'resumen' => $tarjeta[0]->resumenPortal()]);
        $avisosSinLeer = ComNotificacion::dePersona($usuario->per_persona_id)->noLeidas()->count();

        return view('portal.inicio', compact('usuario', 'tarjetas', 'avisosSinLeer'));
    }
}
