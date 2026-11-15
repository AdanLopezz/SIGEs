<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\FiltrarAuditoriaRequest;
use App\Models\SysAuditoria;
use App\Models\User;
use App\Support\Auditoria;

class AuditoriaController extends Controller
{
    public function index(FiltrarAuditoriaRequest $solicitud)
    {
        // Por omisión, los últimos 7 días (hoy incluido).
        $filtros = $solicitud->validated() + ['desde' => today()->subDays(6)->format('Y-m-d'), 'hasta' => today()->format('Y-m-d')];
        $registros = SysAuditoria::with(['usuario', 'detalles'])
            ->entreFechas($filtros['desde'], $filtros['hasta'])
            ->deUsuario(isset($filtros['usuario']) ? (int) $filtros['usuario'] : null)
            ->deTabla($filtros['tabla'] ?? null)
            ->deAccion($filtros['accion'] ?? null)
            ->recientes()->paginarListado();
        // Solo las cuentas que aparecen en la bitácora.
        $usuarios = User::whereIn('id', SysAuditoria::select('user_id')->distinct())->orderBy('name')->pluck('name', 'id');
        $usuarios[Auditoria::sistemaId()] = 'Sistema';
        $tablas = collect(Auditoria::TABLAS)->map(fn ($nombres) => $nombres[0])->sort();
        $acciones = Auditoria::ACCIONES;

        return view('administracion.auditoria.index', compact('registros', 'filtros', 'usuarios', 'tablas', 'acciones'));
    }
}
