<?php

namespace App\Http\Controllers\Prefectura;

use App\Http\Controllers\Controller;
use App\Http\Requests\Prefectura\BuscarAlumnoRequest;
use App\Http\Requests\Prefectura\CancelarPermisoRequest;
use App\Http\Requests\Prefectura\ConfirmarPermisoRequest;
use App\Http\Requests\Prefectura\PermisoSalidaRequest;
use App\Models\PerAlumno;
use App\Models\PerTutorAlumno;
use App\Models\TrxPermisoConfirmacion;
use App\Models\TrxPermisoSalida;
use App\Support\Auditoria;
use Illuminate\Support\Carbon;

class PermisoSalidaController extends Controller
{
    public function index(BuscarAlumnoRequest $solicitud)
    {
        $usuario = $solicitud->user();
        $buscar = $solicitud->validated('buscar');
        $encontrados = filled($buscar)
            ? PerAlumno::with(['persona', 'inscripcionActual.grupo.carrera', 'inscripcionActual.grupo.turno'])->visiblesPara($usuario)
                ->whereHas('inscripciones', fn ($inscripciones) => $inscripciones->inscritas()->delPeriodoActivo())
                ->buscar($buscar)->ordenados()->limit(10)->get()
            : collect();
        $elegido = $solicitud->validated('alumno')
            ? PerAlumno::with(['persona', 'inscripcionActual.grupo'])->visiblesPara($usuario)->find($solicitud->validated('alumno'))
            : null;
        $permisos = TrxPermisoSalida::with(['inscripcion.alumno.persona', 'inscripcion.alumno.tutores.persona', 'inscripcion.grupo', 'solicitadoPor', 'confirmaciones.tutor.persona'])
            ->visiblesPara($usuario)->deHoyEnAdelante()->orderBy('salida_prevista_at')->orderBy('id')->paginarListado();
        $decisiones = TrxPermisoConfirmacion::DECISIONES;
        $metodos = TrxPermisoConfirmacion::METODOS;

        return view('prefectura.permisos-salida.index', compact('buscar', 'encontrados', 'elegido', 'permisos', 'decisiones', 'metodos'));
    }

    public function store(PermisoSalidaRequest $solicitud)
    {
        $datos = $solicitud->validated();
        Auditoria::motivoPorDefecto('Solicitud de permiso de salida');
        TrxPermisoSalida::solicitar(PerAlumno::findOrFail($datos['per_alumno_id']), $datos['motivo'], Carbon::parse($datos['salida_prevista_at']), $solicitud->user());

        return redirect()->route('permisos-salida.index')->with('status', 'Permiso de salida registrado. Falta la confirmación del tutor.');
    }

    public function confirmar(ConfirmarPermisoRequest $solicitud, TrxPermisoSalida $permiso)
    {
        $datos = $solicitud->validated();
        Auditoria::motivoPorDefecto('Confirmación del tutor: '.mb_strtolower(TrxPermisoConfirmacion::DECISIONES[$datos['decision']]));
        $permiso->confirmar(PerTutorAlumno::findOrFail($datos['per_tutor_alumno_id']), $datos['decision'], $datos['metodo'], $datos['evidencia'], $solicitud->user());

        return back()->with('status', 'Confirmación registrada: el permiso quedó '.mb_strtolower(TrxPermisoSalida::ESTADOS[$permiso->estado][0]).'.');
    }

    public function cancelar(CancelarPermisoRequest $solicitud, TrxPermisoSalida $permiso)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        $permiso->cancelar();

        return back()->with('status', 'Permiso de salida cancelado.');
    }
}
