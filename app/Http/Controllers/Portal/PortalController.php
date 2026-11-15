<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\ResuelveAlumnoDelPortal;
use App\Http\Requests\Portal\FiltrarMesRequest;
use App\Models\AcaHorario;
use App\Models\AcaRecursamiento;
use App\Models\PerAlumno;
use App\Models\TrxAsistenciaClase;
use App\Models\TrxAsistenciaDiaria;
use App\Models\TrxIncidencia;
use App\Models\TrxPermisoSalida;
use App\Models\TrxResultadoAsignatura;
use App\Support\PeriodoDeTrabajo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

// Pantallas de solo lectura de "Mi escuela" (Alumno) y "Mis hijos" (Padre de familia).
class PortalController extends Controller
{
    use ResuelveAlumnoDelPortal;

    public function calificaciones(Request $solicitud)
    {
        $alumno = $this->alumnoDelPortal($solicitud);
        // Periodo de consulta: cada materia con sus parciales cerrados, definitiva, estado y oportunidad.
        $periodo = PeriodoDeTrabajo::periodoConsulta();
        $idPeriodo = $periodo?->id ?? 0;
        $inscripcion = $alumno->inscripcionDelPeriodo($idPeriodo)?->load(['grupo.carrera', 'grupo.turno', 'grupo.periodo']);
        $boleta = $inscripcion?->boleta();
        // Recursamientos cursados en el periodo y los de materias que reprobó en él (aunque se cursen después).
        $recursamientos = AcaRecursamiento::with(['materia.asignatura', 'materia.grupo.periodo', 'materia.docente.persona', 'origen.materia.asignatura', 'origen.materia.grupo.periodo'])
            ->delAlumno($alumno->id)
            ->where(fn ($consulta) => $consulta->delPeriodoConsulta($idPeriodo)->orWhere(fn ($origen) => $origen->deReprobadasEn($idPeriodo)))
            ->orderBy('fecha_inicio')->get();
        AcaRecursamiento::cargarResultados($recursamientos);
        // El periodo elegido ya se ve arriba; el historial muestra los demás, cada uno con "Ver detalle".
        $historial = $alumno->historialDeResultados()->toBase()->except([$periodo?->clave]);

        return $this->vista($solicitud, $alumno, 'calificaciones', compact('inscripcion', 'boleta', 'recursamientos', 'historial', 'periodo') + [
            'minima' => TrxResultadoAsignatura::calificacionMinima(),
        ]);
    }

    public function asistencia(FiltrarMesRequest $solicitud)
    {
        $alumno = $this->alumnoDelPortal($solicitud);
        // Solo los meses del periodo de consulta; sin periodo, el mes en curso.
        $periodo = PeriodoDeTrabajo::periodoConsulta();
        $primero = ($periodo?->fecha_inicio ?? today())->copy()->startOfMonth();
        $ultimo = ($periodo?->fecha_fin ?? today())->copy()->startOfMonth();
        $pedido = $solicitud->validated('mes') ? Carbon::createFromFormat('Y-m-d', $solicitud->validated('mes').'-01')->startOfDay() : today()->startOfMonth();
        $mes = $pedido->max($primero)->min($ultimo);
        $dias = $alumno->asistenciaDelMes($mes);
        $totales = collect(array_keys(TrxAsistenciaDiaria::ESTADOS))->mapWithKeys(fn (string $estado) => [$estado => $dias->filter(fn ($valor) => $valor === $estado)->count()]);
        $anterior = $mes->copy()->subMonth()->gte($primero) ? $mes->copy()->subMonth() : null;
        $siguiente = $mes->copy()->addMonth()->lte($ultimo) ? $mes->copy()->addMonth() : null;

        // Por materia (pase de lista por clase) en el periodo de consulta.
        $porMateria = TrxAsistenciaClase::resumenDelAlumno($alumno, PeriodoDeTrabajo::idConsulta());
        $minimo = TrxAsistenciaClase::minimoPorcentaje();

        return $this->vista($solicitud, $alumno, 'asistencia', compact('mes', 'dias', 'totales', 'anterior', 'siguiente', 'porMateria', 'minimo'));
    }

    // Horario semanal del periodo de consulta: las materias de su grupo y las de sus recursamientos, por día.
    public function horario(Request $solicitud)
    {
        $alumno = $this->alumnoDelPortal($solicitud);
        $periodo = PeriodoDeTrabajo::idConsulta();
        $inscripcion = $alumno->inscripcionDelPeriodo($periodo);
        $materias = ($inscripcion ? $inscripcion->grupo()->firstOrFail()->materias()->pluck('id') : collect())
            ->merge(AcaRecursamiento::delAlumno($alumno->id)->whereHas('materia.grupo', fn ($grupos) => $grupos->where('aca_periodo_escolar_id', $periodo))
                ->pluck('aca_grupo_asignatura_id'));
        $porDia = AcaHorario::conDetalle()->whereIn('aca_grupo_asignatura_id', $materias)->ordenados()->get()->groupBy('dia_semana');

        return $this->vista($solicitud, $alumno, 'horario', compact('porDia'));
    }

    public function incidencias(Request $solicitud)
    {
        $alumno = $this->alumnoDelPortal($solicitud);
        // Sin descripción, resolución ni documento: para los detalles se acude con Orientación.
        $incidencias = TrxIncidencia::with('tipo')->deAlumno($alumno->id)->delPeriodo(PeriodoDeTrabajo::idConsulta())->recientes()->paginarListado();

        return $this->vista($solicitud, $alumno, 'incidencias', compact('incidencias'));
    }

    public function permisos(Request $solicitud)
    {
        $alumno = $this->alumnoDelPortal($solicitud);
        // Los del periodo de consulta (por la fecha de salida).
        $periodo = PeriodoDeTrabajo::periodoConsulta();
        $permisos = TrxPermisoSalida::with(['confirmaciones.tutor.persona'])->deAlumno($alumno->id)
            ->whereBetween('salida_prevista_at', [($periodo?->fecha_inicio ?? today())->copy()->startOfDay(), ($periodo?->fecha_fin ?? today())->copy()->endOfDay()])
            ->orderByDesc('salida_prevista_at')->orderByDesc('id')->paginarListado();

        return $this->vista($solicitud, $alumno, 'permisos', compact('permisos'));
    }

    public function credencial(Request $solicitud)
    {
        $alumno = $this->alumnoDelPortal($solicitud)->load('credencialActiva');

        return $this->vista($solicitud, $alumno, 'credencial', ['credencial' => $alumno->credencialActiva]);
    }

    public function datos(Request $solicitud)
    {
        $alumno = $this->alumnoDelPortal($solicitud)->load(['persona', 'carrera', 'estado', 'salud',
            'tutores' => fn ($tutores) => $tutores->activos()->with('persona')->orderByDesc('es_contacto_emergencia')->orderBy('id')]);

        return $this->vista($solicitud, $alumno, 'datos', ['inscripcion' => $alumno->inscripcionDelPeriodo(PeriodoDeTrabajo::idConsulta())?->load(['grupo.carrera', 'grupo.turno', 'grupo.periodo'])]);
    }

    // Datos comunes del encabezado: alumno, contexto (mi-escuela o mis-hijos), su inscripción del periodo y cuántos hijos tiene el padre.
    private function vista(Request $solicitud, PerAlumno $alumno, string $pantalla, array $datos)
    {
        $contexto = $this->contexto($solicitud);
        $alumno->loadMissing('persona');

        return view("portal.{$pantalla}", $datos + [
            'alumno' => $alumno,
            'contexto' => $contexto,
            'pantalla' => $pantalla,
            // Grupo del periodo de consulta.
            'inscripcionEncabezado' => $datos['inscripcion'] ?? $alumno->inscripcionDelPeriodo(PeriodoDeTrabajo::idConsulta())?->load('grupo'),
            'periodoConsulta' => PeriodoDeTrabajo::periodoConsulta(),
            'totalHijos' => $contexto === 'mis-hijos' ? PerAlumno::deTutor($solicitud->user()->per_persona_id)->count() : 0,
        ]);
    }
}
