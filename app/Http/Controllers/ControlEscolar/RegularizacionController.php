<?php

namespace App\Http\Controllers\ControlEscolar;

use App\Http\Controllers\Controller;
use App\Http\Requests\ControlEscolar\ExtraordinarioRequest;
use App\Http\Requests\ControlEscolar\FiltrarRegularizacionRequest;
use App\Http\Requests\ControlEscolar\QuitarRecursamientoRequest;
use App\Http\Requests\ControlEscolar\RecursamientoRequest;
use App\Models\AcaAsignatura;
use App\Models\AcaGrupoAsignatura;
use App\Models\AcaGrupoEscolar;
use App\Models\AcaPeriodoEscolar;
use App\Models\AcaRecursamiento;
use App\Models\TrxResultadoAsignatura;
use App\Support\Auditoria;
use App\Support\PeriodoDeTrabajo;
use Illuminate\Support\Carbon;

class RegularizacionController extends Controller
{
    public function index(FiltrarRegularizacionRequest $solicitud)
    {
        $filtros = $solicitud->validated();
        $filtros['situacion'] ??= TrxResultadoAsignatura::PENDIENTE;
        // Sin filtro de periodo en la petición, el de consulta; "Todos" (vacío) sigue mostrando todos.
        if (! $solicitud->exists('periodo')) {
            $filtros['periodo'] = PeriodoDeTrabajo::periodoConsulta()?->id;
        }
        $situacion = $filtros['situacion'] === FiltrarRegularizacionRequest::TODAS ? null : $filtros['situacion'];
        $resultados = TrxResultadoAsignatura::with(['inscripcion.alumno.persona', 'materia.asignatura', 'materia.grupo.periodo', 'inscripcion.alumno.estado', 'recursamientos.materia.grupo'])
            ->reprobadas()->deSituacion($situacion)
            ->delPeriodo(isset($filtros['periodo']) ? (int) $filtros['periodo'] : null)
            ->deGrupo(isset($filtros['grupo']) ? (int) $filtros['grupo'] : null)
            ->deAsignatura(isset($filtros['asignatura']) ? (int) $filtros['asignatura'] : null)
            ->deOportunidad($filtros['oportunidad'] ?? null)
            ->orderByDesc('fecha_cierre')->orderByDesc('id')->paginarListado();
        AcaRecursamiento::cargarResultados($resultados->getCollection()->flatMap->recursamientos);
        $adeudos = TrxResultadoAsignatura::adeudosPorAlumno($resultados->getCollection()->pluck('inscripcion.per_alumno_id')->unique()->all());
        $maximo = TrxResultadoAsignatura::maximoReprobadas();
        $periodos = AcaPeriodoEscolar::ordenados()->get()->mapWithKeys(fn (AcaPeriodoEscolar $periodo) => [$periodo->id => $periodo->nombre_visible.' ('.$periodo->clave.')']);
        $grupos = AcaGrupoEscolar::with(['carrera', 'turno', 'periodo'])->delPeriodo(isset($filtros['periodo']) ? (int) $filtros['periodo'] : null)->ordenados()->get();
        $asignaturas = AcaAsignatura::ordenados()->get();
        $minimaExtraordinario = TrxResultadoAsignatura::calificacionMinimaExtraordinario();

        return view('control-escolar.regularizacion.index', compact('resultados', 'filtros', 'adeudos', 'maximo', 'periodos', 'grupos', 'asignaturas', 'minimaExtraordinario'));
    }

    public function extraordinario(ExtraordinarioRequest $solicitud, TrxResultadoAsignatura $resultado)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        $resultado->registrarExtraordinario($solicitud->validated('calificacion'), Carbon::parse($solicitud->validated('fecha')), $solicitud->user());

        return back()->with('status', 'Extraordinario registrado: '.($resultado->estado === TrxResultadoAsignatura::APROBADA ? 'aprobado' : 'reprobado')
            .' con '.$resultado->calificacion.'.');
    }

    public function corregirExtraordinario(ExtraordinarioRequest $solicitud, TrxResultadoAsignatura $resultado)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        $resultado->corregirExtraordinario($solicitud->validated('calificacion'), Carbon::parse($solicitud->validated('fecha')), $solicitud->user());

        return back()->with('status', 'Extraordinario corregido: '.$resultado->calificacion.'.');
    }

    public function recursamiento(TrxResultadoAsignatura $resultado)
    {
        $resultado->load(['inscripcion.alumno.persona', 'inscripcion.alumno.estado', 'inscripcion.alumno.inscripcionActual.grupo',
            'materia.asignatura', 'materia.grupo.periodo', 'materia.grupo.carrera', 'materia.grupo.turno']);
        $problemas = $resultado->problemasRecursamiento();
        $destinos = $problemas ? collect() : $resultado->destinosRecursamiento()
            ->with(['grupo.carrera', 'grupo.turno', 'docente.persona'])->ordenados()->get();
        $periodo = AcaPeriodoEscolar::activo();

        return view('control-escolar.regularizacion.recursamiento', compact('resultado', 'problemas', 'destinos', 'periodo'));
    }

    public function asignar(RecursamientoRequest $solicitud, TrxResultadoAsignatura $resultado)
    {
        Auditoria::motivoPorDefecto('Asignación de recursamiento');
        $destino = AcaGrupoAsignatura::with(['asignatura', 'grupo'])->findOrFail($solicitud->validated('aca_grupo_asignatura_id'));
        $resultado->asignarRecursamiento($destino, Carbon::parse($solicitud->validated('fecha_inicio')), $solicitud->user());

        return redirect()->route('regularizacion.index', ['situacion' => TrxResultadoAsignatura::EN_RECURSAMIENTO])
            ->with('status', "Recursamiento asignado en {$destino->nombre}. El docente ya lo ve en su captura de calificaciones.");
    }

    public function quitar(QuitarRecursamientoRequest $solicitud, AcaRecursamiento $recursamiento)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        $recursamiento->quitar();

        return back()->with('status', 'Recursamiento quitado. La materia vuelve a quedar pendiente.');
    }
}
