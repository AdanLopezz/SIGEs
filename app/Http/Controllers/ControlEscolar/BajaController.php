<?php

namespace App\Http\Controllers\ControlEscolar;

use App\Http\Controllers\Controller;
use App\Http\Requests\ControlEscolar\BajaRequest;
use App\Http\Requests\ControlEscolar\FiltrarBajasRequest;
use App\Http\Requests\ControlEscolar\ReingresoRequest;
use App\Models\AcaBaja;
use App\Models\AcaGrupoEscolar;
use App\Models\AcaInscripcion;
use App\Models\AcaPeriodoEscolar;
use App\Models\PerAlumno;
use App\Support\Auditoria;
use App\Support\PeriodoDeTrabajo;
use Illuminate\Support\Carbon;

class BajaController extends Controller
{
    public function index(FiltrarBajasRequest $solicitud)
    {
        $filtros = $solicitud->validated();
        // Por defecto, el periodo de consulta (selector de la barra superior).
        $filtros['periodo'] ??= PeriodoDeTrabajo::periodoConsulta()?->id;
        $bajas = AcaBaja::with(['inscripcion.alumno.persona', 'inscripcion.grupo.carrera', 'inscripcion.grupo.turno', 'inscripcion.grupo.periodo', 'registradoPor'])
            ->delPeriodo($filtros['periodo'] ? (int) $filtros['periodo'] : null)
            ->deTipo($filtros['tipo'] ?? null)
            ->entreFechas($filtros['desde'] ?? null, $filtros['hasta'] ?? null)
            ->orderByDesc('fecha')->orderByDesc('id')->paginarListado();
        $periodos = AcaPeriodoEscolar::ordenados()->get()->mapWithKeys(fn (AcaPeriodoEscolar $periodo) => [$periodo->id => $periodo->nombre_visible.' ('.$periodo->clave.')']);
        $tipos = AcaBaja::TIPOS;

        return view('control-escolar.bajas.index', compact('bajas', 'filtros', 'periodos', 'tipos'));
    }

    public function store(BajaRequest $solicitud, PerAlumno $alumno)
    {
        $datos = $solicitud->validated();
        $alumno->darDeBaja($datos['tipo'], Carbon::parse($datos['fecha']), $datos['motivo'], $solicitud->user());

        return redirect()->route('alumnos.show', $alumno)->with('status', 'Baja '.mb_strtolower(AcaBaja::TIPOS[$datos['tipo']]).' registrada. Sus credenciales quedaron desactivadas.');
    }

    public function reingreso(PerAlumno $alumno)
    {
        $alumno->load(['persona', 'carrera', 'estado']);
        // Grupos válidos: periodo activo y su carrera, sin el grupo del que se dio de baja.
        $conBaja = $alumno->inscripciones()->where('estado', AcaInscripcion::BAJA)->pluck('aca_grupo_escolar_id');
        $grupos = AcaGrupoEscolar::with(['carrera', 'turno'])->delPeriodoActivo()->deCarrera($alumno->aca_carrera_id)
            ->whereNotIn('id', $conBaja)->ordenados()->get();

        return view('control-escolar.alumnos.reingreso', compact('alumno', 'grupos'));
    }

    public function reingresar(ReingresoRequest $solicitud, PerAlumno $alumno)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        $alumno->reingresar(AcaGrupoEscolar::findOrFail($solicitud->validated('aca_grupo_escolar_id')), $solicitud->user());

        return redirect()->route('alumnos.show', $alumno)->with('status', 'Reingreso registrado: el alumno vuelve a estar Activo e inscrito. Emite una credencial nueva si la necesita.');
    }
}
