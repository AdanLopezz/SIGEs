<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administracion\FiltrarNotificacionesRequest;
use App\Http\Requests\Administracion\ReintentarNotificacionRequest;
use App\Models\ComNotificacion;
use App\Support\EnvioDeNotificaciones;

class NotificacionController extends Controller
{
    public function index(FiltrarNotificacionesRequest $solicitud, EnvioDeNotificaciones $envios)
    {
        $filtros = $solicitud->validated();
        $avisos = ComNotificacion::with('persona')->withCount('intentos')
            ->when($filtros['estado'] ?? null, fn ($consulta, $estado) => $consulta->where('estado', $estado))
            ->recientes()->paginarListado();
        $habilitado = $envios->habilitado();

        return response()->view('administracion.notificaciones.index', compact('avisos', 'filtros', 'habilitado'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function show(ComNotificacion $notificacion)
    {
        $notificacion->load('persona');
        $intentos = $notificacion->intentos()->latest('id')->paginarListado();

        return response()->view('administracion.notificaciones.show', compact('notificacion', 'intentos'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function reintentar(ReintentarNotificacionRequest $solicitud, ComNotificacion $notificacion, EnvioDeNotificaciones $envios)
    {
        $envios->reprogramar($notificacion, $solicitud->validated('motivo'), $solicitud->boolean('entrega_descartada'));

        return redirect()->route('notificaciones.show', $notificacion)->with('status', 'Reintento registrado. Se procesará cuando el envío esté habilitado.');
    }
}
