<?php

namespace App\Http\Controllers\Orientacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orientacion\AbrirSeguimientoRequest;
use App\Http\Requests\Orientacion\BuscarAlumnoSeguimientoRequest;
use App\Http\Requests\Orientacion\CerrarSeguimientoRequest;
use App\Http\Requests\Orientacion\ContinuarSeguimientoRequest;
use App\Http\Requests\Orientacion\FiltrarAlumnosAAtenderRequest;
use App\Http\Requests\Orientacion\FiltrarSeguimientoRequest;
use App\Http\Requests\Orientacion\NotaSeguimientoRequest;
use App\Http\Requests\Orientacion\ReabrirSeguimientoRequest;
use App\Http\Requests\Orientacion\RiesgoSeguimientoRequest;
use App\Models\AcaGrupoEscolar;
use App\Models\PerAlumno;
use App\Models\TrxAsistenciaClase;
use App\Models\TrxResultadoAsignatura;
use App\Models\TrxSeguimientoAlumno;
use App\Models\TrxSeguimientoNota;
use App\Support\AlumnosAAtender;
use App\Support\Auditoria;
use App\Support\Paginacion;
use App\Support\PeriodoDeTrabajo;
use Illuminate\Http\Request;

/**
 * Orientación → Seguimiento (seguimiento.administrar). Visibilidad: TrxSeguimientoAlumno::scopeVisiblesPara + SeguimientoPolicy.
 * Nada de esto se muestra en el portal.
 */
class SeguimientoController extends Controller
{
    public function index(FiltrarSeguimientoRequest $solicitud)
    {
        $usuario = $solicitud->user();
        $filtros = $solicitud->validated();
        // Los casos del periodo de consulta (por la inscripción del caso).
        $casos = TrxSeguimientoAlumno::with(['inscripcion.alumno.persona', 'inscripcion.grupo', 'ultimaNota'])->withCount('notas')
            ->visiblesPara($usuario)->delPeriodo(PeriodoDeTrabajo::idConsulta())
            ->deEstado($filtros['estado'] ?? null)
            ->deMotivo($filtros['motivo'] ?? null)
            ->deGrupo(isset($filtros['grupo']) ? (int) $filtros['grupo'] : null)
            ->enRiesgo(! empty($filtros['riesgo']))
            ->deAlumno(isset($filtros['alumno']) ? (int) $filtros['alumno'] : null)
            ->recientes()->paginarListado();
        $grupos = $this->grupos($solicitud);
        $alumnoFiltrado = isset($filtros['alumno']) ? PerAlumno::with('persona')->find($filtros['alumno']) : null;
        $estados = TrxSeguimientoAlumno::ESTADOS;
        $motivos = TrxSeguimientoAlumno::MOTIVOS;
        $puedeAbrir = PeriodoDeTrabajo::permite(PeriodoDeTrabajo::OPERAR);

        return view('orientacion.seguimiento.index', compact('casos', 'filtros', 'grupos', 'alumnoFiltrado', 'estados', 'motivos', 'puedeAbrir'));
    }

    // Alertas de solo lectura: alumnos de mis grupos del periodo de consulta con motivo sugerido.
    public function alumnos(FiltrarAlumnosAAtenderRequest $solicitud)
    {
        $filtros = $solicitud->validated();
        $filas = Paginacion::paginarColeccion(
            AlumnosAAtender::para($solicitud->user(), PeriodoDeTrabajo::idConsulta(), isset($filtros['grupo']) ? (int) $filtros['grupo'] : null, $filtros['motivo'] ?? null),
            $solicitud);
        $grupos = $this->grupos($solicitud);
        $motivos = collect(AlumnosAAtender::MOTIVOS)->mapWithKeys(fn ($motivo) => [$motivo => TrxSeguimientoAlumno::MOTIVOS[$motivo]])->all();
        $puedeAbrir = PeriodoDeTrabajo::permite(PeriodoDeTrabajo::OPERAR);
        $reglas = ['minimo' => TrxAsistenciaClase::minimoPorcentaje(), 'maximo' => TrxResultadoAsignatura::maximoReprobadas()];

        return view('orientacion.seguimiento.alumnos', compact('filas', 'filtros', 'grupos', 'motivos', 'puedeAbrir', 'reglas'));
    }

    // Resumen para la Subdirección: de los casos visibles del periodo de consulta.
    public function resumen(FiltrarSeguimientoRequest $solicitud)
    {
        $resumen = TrxSeguimientoAlumno::resumenDe(TrxSeguimientoAlumno::query()->visiblesPara($solicitud->user())->delPeriodo(PeriodoDeTrabajo::idConsulta()));
        $grupos = AcaGrupoEscolar::with(['carrera', 'turno'])->whereKey($resumen['porGrupo']->keys())->ordenados()->get();
        $estados = TrxSeguimientoAlumno::ESTADOS;
        $motivos = TrxSeguimientoAlumno::MOTIVOS;

        return view('orientacion.seguimiento.resumen', compact('resumen', 'grupos', 'estados', 'motivos'));
    }

    public function create(BuscarAlumnoSeguimientoRequest $solicitud)
    {
        $usuario = $solicitud->user();
        PeriodoDeTrabajo::exigir(PeriodoDeTrabajo::OPERAR, PeriodoDeTrabajo::periodoActivo());
        $buscar = $solicitud->validated('buscar');
        $motivo = $solicitud->validated('motivo');
        $encontrados = filled($buscar)
            ? TrxSeguimientoAlumno::alumnosElegibles($usuario)->with(['persona', 'inscripcionActual.grupo'])->buscar($buscar)->ordenados()->limit(10)->get()
            : collect();
        $elegido = $solicitud->validated('alumno')
            ? TrxSeguimientoAlumno::alumnosElegibles($usuario)->with(['persona', 'inscripcionActual.grupo'])->find($solicitud->validated('alumno'))
            : null;
        // Casos abiertos del alumno elegido (para el aviso de motivo repetido).
        $abiertos = $elegido
            ? TrxSeguimientoAlumno::with('inscripcion.grupo.periodo')->abiertos()->visiblesPara($usuario)->deAlumno($elegido->id)->recientes()->get()
            : collect();
        $motivos = TrxSeguimientoAlumno::MOTIVOS;

        return view('orientacion.seguimiento.form', compact('buscar', 'encontrados', 'elegido', 'abiertos', 'motivo', 'motivos'));
    }

    public function store(AbrirSeguimientoRequest $solicitud)
    {
        $caso = TrxSeguimientoAlumno::abrir($solicitud->safe()->except('confirmar_duplicado'), $solicitud->user(), $solicitud->boolean('confirmar_duplicado'));

        return redirect()->route('seguimiento.show', $caso)->with('status', 'Caso de seguimiento abierto.');
    }

    public function show(TrxSeguimientoAlumno $seguimiento)
    {
        $seguimiento->load(['inscripcion.alumno.persona', 'inscripcion.grupo.periodo', 'abiertoPor',
            'notas' => fn ($notas) => $notas->with('registradoPor')->orderByDesc('fecha')->orderByDesc('id')]);
        $tipos = TrxSeguimientoNota::TIPOS;

        return view('orientacion.seguimiento.show', ['caso' => $seguimiento, 'tipos' => $tipos]);
    }

    public function nota(NotaSeguimientoRequest $solicitud, TrxSeguimientoAlumno $seguimiento)
    {
        $seguimiento->agregarNota($solicitud->validated(), $solicitud->user());

        return redirect()->route('seguimiento.show', $seguimiento)->with('status', 'Nota agregada.');
    }

    public function cerrar(CerrarSeguimientoRequest $solicitud, TrxSeguimientoAlumno $seguimiento)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        $seguimiento->cerrar($solicitud->safe()->except('motivo'), $solicitud->user());

        return redirect()->route('seguimiento.show', $seguimiento)->with('status', 'Caso cerrado.');
    }

    public function continuar(ContinuarSeguimientoRequest $solicitud, TrxSeguimientoAlumno $seguimiento)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        $nuevo = $seguimiento->continuarEnPeriodoActual($solicitud->safe()->except('motivo'), $solicitud->user());

        return redirect()->route('seguimiento.show', $nuevo)->with('status', 'El caso #'.$seguimiento->id.' quedó cerrado y continúa en este caso.');
    }

    public function reabrir(ReabrirSeguimientoRequest $solicitud, TrxSeguimientoAlumno $seguimiento)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        $seguimiento->reabrir();

        return redirect()->route('seguimiento.show', $seguimiento)->with('status', 'Caso reabierto.');
    }

    public function riesgo(RiesgoSeguimientoRequest $solicitud, TrxSeguimientoAlumno $seguimiento)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        $seguimiento->cambiarRiesgo($solicitud->boolean('riesgo_abandono'));

        return redirect()->route('seguimiento.show', $seguimiento)->with('status', 'Riesgo de abandono actualizado.');
    }

    // Grupos que acompaño en el periodo de consulta (filtro).
    private function grupos(Request $solicitud)
    {
        return AcaGrupoEscolar::with(['carrera', 'turno'])->acompanadosPor($solicitud->user(), PeriodoDeTrabajo::idConsulta())->ordenados()->get();
    }
}
