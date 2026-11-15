<?php

namespace App\Http\Controllers\ControlEscolar;

use App\Http\Controllers\Controller;
use App\Http\Requests\ControlEscolar\CambioPeriodoAlumnosRequest;
use App\Http\Requests\ControlEscolar\CambioPeriodoConfirmarRequest;
use App\Http\Requests\ControlEscolar\CambioPeriodoGruposRequest;
use App\Http\Requests\ControlEscolar\CambioPeriodoRequest;
use App\Models\AcaGrupoEscolar;
use App\Models\AcaPeriodoEscolar;
use App\Models\PerPersonal;
use App\Models\TrxResultadoAsignatura;
use App\Support\CambioDePeriodo;
use App\Support\Paginacion;
use App\Support\PeriodoDeTrabajo;
use Illuminate\Http\Request;

// Asistente "Cambio de periodo y reinscripción" (CambioDePeriodo). Lo elegido entre pasos va en la sesión.
class CambioPeriodoController extends Controller
{
    // Paso 1: periodos origen y destino.
    public function index(CambioPeriodoRequest $solicitud)
    {
        $origenes = CambioDePeriodo::origenes();
        $destinos = CambioDePeriodo::destinos();
        $borrador = session(CambioDePeriodo::SESION, []);
        $activo = PeriodoDeTrabajo::periodoActivo();
        $origen = $origenes->firstWhere('id', (int) ($solicitud->validated('origen') ?? $borrador['origen'] ?? $activo?->id)) ?? $origenes->first();
        $paraOrigen = $origen ? CambioDePeriodo::destinosPara($origen) : collect();
        $destino = $paraOrigen->firstWhere('id', (int) ($solicitud->validated('destino') ?? $borrador['destino'] ?? 0)) ?? $paraOrigen->first();

        return view('control-escolar.cambio-periodo.periodos', compact('origenes', 'destinos', 'origen', 'destino'));
    }

    // Paso 2: propuesta de grupos.
    public function grupos(CambioPeriodoRequest $solicitud)
    {
        $origen = AcaPeriodoEscolar::find($solicitud->validated('origen'));
        $destino = AcaPeriodoEscolar::find($solicitud->validated('destino'));
        if (! $origen || ! $destino) {
            return redirect()->route('cambio-periodo.index')->withErrors(['destino' => 'Elige el periodo origen y el destino.']);
        }
        CambioDePeriodo::validarPeriodos($origen, $destino);
        // El borrador se conserva si es de los mismos periodos; "Conservar orientador y tutor" vuelve a proponer los grupos.
        $borrador = session(CambioDePeriodo::SESION, []);
        $mismo = ($borrador['origen'] ?? null) === $origen->id && ($borrador['destino'] ?? null) === $destino->id;
        if (! $mismo || $solicitud->has('conservar')) {
            $conservar = $solicitud->has('conservar') ? $solicitud->boolean('conservar') : true;
            $borrador = CambioDePeriodo::borradorNuevo($origen, $destino, $conservar) + ($mismo ? ['alumnos' => $borrador['alumnos'] ?? [], 'revisados' => $borrador['revisados'] ?? []] : []);
            session([CambioDePeriodo::SESION => $borrador]);
        }
        $cambio = new CambioDePeriodo($origen, $destino, $borrador, $solicitud->user()->can('horarios.administrar'));
        $orientadores = PerPersonal::with('persona')->conRol('Orientador')->ordenados()->get()->mapWithKeys(fn (PerPersonal $personal) => [$personal->id => $personal->nombre]);
        $tutores = PerPersonal::with('persona')->conRol('Tutor')->ordenados()->get()->mapWithKeys(fn (PerPersonal $personal) => [$personal->id => $personal->nombre]);

        return view('control-escolar.cambio-periodo.grupos', compact('cambio', 'orientadores', 'tutores'));
    }

    public function guardarGrupos(CambioPeriodoGruposRequest $solicitud)
    {
        $cambio = $this->cambio($solicitud);
        if (! $cambio) {
            return $this->sinBorrador();
        }
        $cambio->guardarGrupos($solicitud->validated('grupos'));
        session([CambioDePeriodo::SESION => $cambio->borrador()]);
        $parametros = ['origen' => $cambio->origen->id, 'destino' => $cambio->destino->id];
        if ($cambio->conflictos()->isNotEmpty()) {
            return redirect()->route('cambio-periodo.grupos', $parametros)
                ->withErrors(['accion' => 'Corrige los grupos marcados con conflicto: '.$cambio->conflictos()->pluck('identificador')->implode(', ').'.']);
        }

        return redirect()->route('cambio-periodo.alumnos')->with('status', 'Grupos listos. Revisa a los alumnos y confirma.');
    }

    // Paso 3: alumnos, por grupo.
    public function alumnos(Request $solicitud)
    {
        $cambio = $this->cambio($solicitud);
        if (! $cambio || ! $cambio->gruposListos()) {
            return $cambio ? redirect()->route('cambio-periodo.grupos', ['origen' => $cambio->origen->id, 'destino' => $cambio->destino->id]) : $this->sinBorrador();
        }
        $todos = $cambio->resumenPorGrupo();
        $grupos = Paginacion::paginarColeccion($todos, $solicitud);
        $totales = collect(array_keys(CambioDePeriodo::SITUACIONES))->mapWithKeys(fn (string $situacion) => [$situacion => $todos->sum(fn ($fila) => $fila->situaciones[$situacion])]);
        $marcados = $todos->sum('marcados');
        $sinCerrar = $cambio->materiasSinCerrar()->sum();
        $porCrear = $cambio->propuesta()->where('estado', CambioDePeriodo::CREAR)->count();
        $porReutilizar = $cambio->propuesta()->where('estado', CambioDePeriodo::REUTILIZAR)->count();

        return view('control-escolar.cambio-periodo.alumnos', compact('cambio', 'grupos', 'totales', 'marcados', 'sinCerrar', 'porCrear', 'porReutilizar'));
    }

    public function revisar(Request $solicitud, AcaGrupoEscolar $grupo)
    {
        $cambio = $this->cambioDelGrupo($solicitud, $grupo);
        $filas = $cambio->revision()->where('grupo.id', $grupo->id)->values();
        $maximo = TrxResultadoAsignatura::maximoReprobadas();
        $sinCerrar = $cambio->materiasSinCerrar()->get($grupo->id, 0);

        return view('control-escolar.cambio-periodo.revisar-grupo', compact('cambio', 'grupo', 'filas', 'maximo', 'sinCerrar'));
    }

    public function guardarRevision(CambioPeriodoAlumnosRequest $solicitud, AcaGrupoEscolar $grupo)
    {
        $cambio = $this->cambioDelGrupo($solicitud, $grupo);
        $cambio->guardarRevision($grupo, $solicitud->validated('alumnos') ?? []);
        session([CambioDePeriodo::SESION => $cambio->borrador()]);

        return redirect()->route('cambio-periodo.alumnos')->with('status', "Revisión del grupo {$grupo->identificador} guardada.");
    }

    // Paso 4: confirmar y resumen.
    public function confirmar(CambioPeriodoConfirmarRequest $solicitud)
    {
        $cambio = $this->cambio($solicitud);
        if (! $cambio || ! $cambio->gruposListos()) {
            return $this->sinBorrador();
        }
        $resumen = $cambio->confirmar($solicitud->user(), $solicitud->boolean('acepto_pendientes'));
        session()->forget(CambioDePeriodo::SESION);
        session([CambioDePeriodo::SESION_RESUMEN => $resumen]);
        $reinscritos = collect($resumen['alumnos'])->where('resultado', CambioDePeriodo::REINSCRITO)->count();

        return redirect()->route('cambio-periodo.resumen')->with('status', "Reinscripción terminada: {$reinscritos} ".($reinscritos === 1 ? 'alumno reinscrito' : 'alumnos reinscritos').'.');
    }

    public function resumen(CambioPeriodoRequest $solicitud)
    {
        $resumen = session(CambioDePeriodo::SESION_RESUMEN);
        if (! $resumen) {
            return redirect()->route('cambio-periodo.index')->withErrors(['accion' => 'No hay un cambio de periodo reciente en esta sesión. Lo hecho queda en la bitácora.']);
        }
        $resultado = $solicitud->validated('resultado');
        $todos = collect($resumen['alumnos']);
        $alumnos = Paginacion::paginarColeccion($resultado ? $todos->where('resultado', $resultado)->values() : $todos, $solicitud);
        $totales = collect(CambioDePeriodo::RESULTADOS)->map(fn ($nombre, string $clave) => $todos->where('resultado', $clave)->count());

        return view('control-escolar.cambio-periodo.resumen', compact('resumen', 'alumnos', 'totales', 'resultado'));
    }

    public function imprimir()
    {
        $resumen = session(CambioDePeriodo::SESION_RESUMEN);
        if (! $resumen) {
            return redirect()->route('cambio-periodo.index');
        }
        $alumnos = collect($resumen['alumnos']);

        return view('control-escolar.cambio-periodo.imprimir', compact('resumen', 'alumnos'));
    }

    private function cambio(Request $solicitud): ?CambioDePeriodo
    {
        return CambioDePeriodo::desdeSesion($solicitud->user());
    }

    // El grupo debe ser del origen del borrador y los grupos deben estar listos.
    private function cambioDelGrupo(Request $solicitud, AcaGrupoEscolar $grupo): CambioDePeriodo
    {
        $cambio = $this->cambio($solicitud);
        abort_unless($cambio && $cambio->gruposListos() && (int) $grupo->aca_periodo_escolar_id === $cambio->origen->id, 404);

        return $cambio;
    }

    private function sinBorrador()
    {
        return redirect()->route('cambio-periodo.index')->withErrors(['accion' => 'Empieza eligiendo los periodos origen y destino.']);
    }
}
