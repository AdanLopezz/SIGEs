<?php

namespace App\Http\Controllers\ControlEscolar;

use App\Http\Controllers\Controller;
use App\Http\Requests\ControlEscolar\ConsentimientoRequest;
use App\Models\PerAlumno;
use App\Models\PerConsentimiento;
use App\Support\Auditoria;
use Illuminate\Support\Carbon;

class ConsentimientoController extends Controller
{
    public function index(PerAlumno $alumno)
    {
        $alumno->load(['persona', 'tutores' => fn ($tutores) => $tutores->activos()->with('persona')->orderByDesc('es_contacto_emergencia')->orderBy('id')]);
        $matriz = PerConsentimiento::matrizVigentes($alumno->tutores->pluck('id')->all());
        $historial = $alumno->consentimientos()->with(['tutor.persona', 'archivo'])->recientes()->get();
        $tipos = PerConsentimiento::TIPOS;

        return view('control-escolar.alumnos.consentimientos', compact('alumno', 'matriz', 'historial', 'tipos'));
    }

    public function store(ConsentimientoRequest $solicitud, PerAlumno $alumno)
    {
        $datos = $solicitud->validated();
        $tutor = $alumno->tutores()->activos()->findOrFail($datos['per_tutor_alumno_id']);
        $otorgado = $solicitud->boolean('otorgado');
        Auditoria::motivoPorDefecto(($otorgado ? 'Otorgamiento' : 'Revocación').' del consentimiento: '.mb_strtolower(PerConsentimiento::TIPOS[$datos['tipo']]));
        $tutor->registrarConsentimiento($datos['tipo'], $otorgado, Carbon::parse($datos['fecha']), $solicitud->file('documento'), $solicitud->user());

        return redirect()->route('alumnos.consentimientos.index', $alumno)->with('status', 'Consentimiento '.($otorgado ? 'otorgado' : 'revocado').' registrado correctamente.');
    }
}
