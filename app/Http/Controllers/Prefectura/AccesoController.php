<?php

namespace App\Http\Controllers\Prefectura;

use App\Http\Controllers\Controller;
use App\Http\Requests\Prefectura\AccesoManualRequest;
use App\Http\Requests\Prefectura\BuscarAlumnoRequest;
use App\Http\Requests\Prefectura\RegistrarAccesoRequest;
use App\Models\PerAlumno;
use App\Models\PerCredencialAcceso;
use App\Models\TrxRegistroAcceso;
use App\Support\ResultadoAcceso;

class AccesoController extends Controller
{
    public function index(BuscarAlumnoRequest $solicitud)
    {
        $buscar = $solicitud->validated('buscar');
        // Registro manual: hasta 10 coincidencias.
        $encontrados = filled($buscar)
            ? PerAlumno::with(['persona', 'credencialActiva', 'inscripcionActual.grupo.carrera', 'inscripcionActual.grupo.turno'])
                ->visiblesPara($solicitud->user())->buscar($buscar)->ordenados()->limit(10)->get()
            : collect();
        $ultimos = $this->ultimos();
        $resultado = session('resultado_acceso');

        return view('prefectura.acceso.index', compact('buscar', 'encontrados', 'ultimos', 'resultado'));
    }

    // Lectura del lector QR o de la cámara (fetch). Responde con el resultado y los últimos registros ya renderizados.
    public function registrar(RegistrarAccesoRequest $solicitud)
    {
        $token = $solicitud->token();
        $credencial = $token ? PerCredencialAcceso::buscarPorToken($token) : null;
        $resultado = $credencial
            ? TrxRegistroAcceso::registrar($credencial, TrxRegistroAcceso::QR, $solicitud->user())
            : ResultadoAcceso::rechazo('Credencial no reconocida. Verifica que sea una credencial de SIGEs.');
        $datos = $resultado->aArreglo();

        if (! $solicitud->expectsJson()) {
            return redirect()->route('acceso.index')->with('resultado_acceso', $datos);
        }

        return response()->json([
            'resultado' => view('prefectura.acceso._resultado', ['resultado' => $datos])->render(),
            'ultimos' => view('prefectura.acceso._ultimos', ['ultimos' => $this->ultimos()])->render(),
        ]);
    }

    public function manual(AccesoManualRequest $solicitud)
    {
        $alumno = PerAlumno::with('credencialActiva')->findOrFail($solicitud->validated('per_alumno_id'));
        // La FK de trx_registro_acceso exige una credencial: sin ella no hay registro.
        if (! $alumno->credencialActiva) {
            return back()->withErrors(['accion' => 'El alumno no tiene una credencial activa. Emite su credencial en Control Escolar.']);
        }
        $resultado = TrxRegistroAcceso::registrar($alumno->credencialActiva, TrxRegistroAcceso::MANUAL, $solicitud->user());

        return redirect()->route('acceso.index')->with('resultado_acceso', $resultado->aArreglo());
    }

    private function ultimos()
    {
        return TrxRegistroAcceso::with(['credencial.alumno.persona', 'credencial.alumno.inscripcionActual.grupo'])
            ->delDia(now())->orderByDesc('fecha_hora')->orderByDesc('id')->limit(10)->get();
    }
}
