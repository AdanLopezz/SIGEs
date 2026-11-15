<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\ResuelveAlumnoDelPortal;
use App\Models\PerAlumno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

// "Mis hijos": selector de hijo; el elegido se recuerda en la sesión.
class HijoController extends Controller
{
    use ResuelveAlumnoDelPortal;

    public function index(Request $solicitud)
    {
        $hijos = PerAlumno::with(['persona', 'inscripcionActual.grupo.turno'])->deTutor($solicitud->user()->per_persona_id)->ordenados()->get();
        $elegido = (int) $solicitud->session()->get(self::HIJO_ELEGIDO);

        return view('portal.hijos', compact('hijos', 'elegido'));
    }

    public function elegir(Request $solicitud, PerAlumno $alumno)
    {
        Gate::authorize('portal-hijo', $alumno);
        $solicitud->session()->put(self::HIJO_ELEGIDO, $alumno->id);

        return redirect()->route('mis-hijos.calificaciones.index');
    }
}
