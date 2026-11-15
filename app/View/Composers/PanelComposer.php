<?php

namespace App\View\Composers;

use App\Models\ComNotificacion;
use App\Policies\PortalPolicy;
use App\Models\SysConfiguracion;
use App\Support\Menu;
use App\Support\PeriodoDeTrabajo;
use Illuminate\View\View;

class PanelComposer
{
    public function compose(View $vista): void
    {
        $usuario = auth()->user()->loadMissing(['persona', 'roles']);
        $vista->with([
            'plantel' => SysConfiguracion::actual()?->nombre_corto ?: 'Plantel sin configurar',
            // Selector de periodo: el de consulta (sesión), el activo oficial y los que el usuario puede elegir.
            'periodoActivo' => PeriodoDeTrabajo::periodoActivo(),
            'periodoConsulta' => PeriodoDeTrabajo::periodoConsulta(),
            'tipoPeriodo' => PeriodoDeTrabajo::tipo(),
            'periodosSelector' => $usuario->debe_cambiar_password ? collect() : PeriodoDeTrabajo::periodosPermitidos($usuario),
            // Cuentas solo de Alumno o Padre de familia: un periodo futuro se presenta como "Próximo periodo".
            'soloPortal' => $usuario->roles->isNotEmpty() && $usuario->roles->pluck('name')->diff(PortalPolicy::ROLES)->isEmpty(),
            'usuarioPanel' => $usuario,
            // Con la contraseña temporal pendiente de cambiar no hay menú: solo "Cambiar mi contraseña" y salir.
            'menu' => $usuario->debe_cambiar_password ? [] : Menu::para($usuario),
            // Campana de avisos: solo para cuentas con Alumno o Padre de familia.
            'avisosSinLeer' => $usuario->hasAnyRole(PortalPolicy::ROLES)
                ? ComNotificacion::dePersona($usuario->per_persona_id)->noLeidas()->count() : null,
        ]);
    }
}
