<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ComNotificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

// Avisos (com_notificacion) de la persona de la cuenta. Abrir uno lo marca como LEIDA.
class AvisoController extends Controller
{
    public function index(Request $solicitud)
    {
        $avisos = ComNotificacion::dePersona($solicitud->user()->per_persona_id)->recientes()->paginarListado();

        return view('portal.avisos.index', compact('avisos'));
    }

    public function show(ComNotificacion $aviso)
    {
        Gate::authorize('portal-aviso', $aviso);
        $aviso->marcarLeida();

        return view('portal.avisos.show', compact('aviso'));
    }
}
