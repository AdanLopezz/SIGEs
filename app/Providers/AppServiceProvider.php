<?php

namespace App\Providers;

use App\Policies\PortalPolicy;
use App\Support\Auditoria;
use App\View\Composers\PanelComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // El motivo de la bitácora vive solo durante la petición.
        $this->app->scoped(Auditoria::class);
        // Periodo activo y de consulta: se calculan una vez por petición.
        $this->app->scoped(\App\Support\PeriodoDeTrabajo::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        // Listados: ->paginarListado() = registros por página del usuario + filtros + redirección a la última página (App\Support\Paginacion).
        \Illuminate\Database\Eloquent\Builder::macro('paginarListado', function (?string $pantalla = null) {
            /** @var \Illuminate\Database\Eloquent\Builder $this */
            return \App\Support\Paginacion::paginar($this, request(), $pantalla);
        });
        View::composer('layouts.app', PanelComposer::class);

        // Portal de Alumno y Padre de familia (PerAlumno ya tiene AlumnoPolicy).
        Gate::define('portal-propio', [PortalPolicy::class, 'propio']);
        Gate::define('portal-hijo', [PortalPolicy::class, 'hijo']);
        Gate::define('portal-confirmar-permiso', [PortalPolicy::class, 'confirmarPermiso']);
        Gate::define('portal-aviso', [PortalPolicy::class, 'verAviso']);
    }
}
