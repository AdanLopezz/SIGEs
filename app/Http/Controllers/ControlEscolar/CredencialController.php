<?php

namespace App\Http\Controllers\ControlEscolar;

use App\Http\Controllers\Controller;
use App\Models\AcaPeriodoEscolar;
use App\Models\PerAlumno;
use App\Models\PerCredencialAcceso;
use App\Models\SysConfiguracion;
use App\Support\CodigoQr;
use Illuminate\Validation\ValidationException;

class CredencialController extends Controller
{
    // POST → redirect → GET: el token viaja una sola vez en la sesión; recargar no emite otra credencial.
    public function emitir(PerAlumno $alumno)
    {
        try {
            $token = $alumno->emitirCredencial(auth()->user());
        } catch (ValidationException $excepcion) {
            return back()->withErrors($excepcion->errors());
        }

        return redirect()->route('alumnos.credencial.imprimir', $alumno)->with('credencial_token', $token);
    }

    public function imprimir(PerAlumno $alumno)
    {
        $alumno->load(['persona', 'carrera', 'estado', 'credencialActiva', 'inscripcionActual.grupo.turno']);
        $token = session('credencial_token');
        // El token debe corresponder a la credencial activa de este alumno.
        $vigente = $token && $alumno->credencialActiva && hash_equals($alumno->credencialActiva->token_hash, PerCredencialAcceso::hashDe($token));
        $qr = $vigente ? CodigoQr::svg(route('credenciales.verificar', $token)) : null;
        $plantel = SysConfiguracion::actual();
        $periodo = AcaPeriodoEscolar::activo();

        return view('control-escolar.alumnos.credencial', compact('alumno', 'qr', 'plantel', 'periodo'));
    }

    public function verificar(string $token)
    {
        $credencial = PerCredencialAcceso::buscarPorToken($token);
        $credencial?->load(['alumno.persona', 'alumno.carrera', 'alumno.estado', 'alumno.inscripcionActual.grupo.turno']);
        $alumno = $credencial?->alumno;
        // Además de activa, el alumno debe seguir Activo.
        $valida = $credencial?->activa && $alumno?->estaActivo();

        return view('control-escolar.credenciales.verificar', compact('credencial', 'alumno', 'valida'));
    }
}
