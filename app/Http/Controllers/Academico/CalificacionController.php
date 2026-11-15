<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\FiltrarMateriasRequest;
use App\Http\Requests\Academico\GuardarCalificacionesRequest;
use App\Models\AcaAsignatura;
use App\Models\AcaGrupoAsignatura;
use App\Models\AcaGrupoEscolar;
use App\Models\AcaInscripcion;
use App\Models\AcaRecursamiento;
use App\Models\TrxCalificacion;
use App\Models\TrxResultadoAsignatura;
use App\Support\Auditoria;
use App\Support\PeriodoDeTrabajo;
use Illuminate\Support\Collection;

class CalificacionController extends Controller
{
    public function index(FiltrarMateriasRequest $solicitud)
    {
        $usuario = $solicitud->user();
        $filtros = $solicitud->validated();
        $visibles = fn () => AcaGrupoAsignatura::visiblesParaCalificar($usuario);
        $grupos = AcaGrupoEscolar::with(['carrera', 'turno'])->whereIn('id', $visibles()->select('aca_grupo_escolar_id'))->ordenados()->get();
        $asignaturas = AcaAsignatura::whereIn('id', $visibles()->deGrupo(isset($filtros['grupo']) ? (int) $filtros['grupo'] : null)->select('aca_asignatura_id'))
            ->ordenados()->get();
        $materias = $visibles()->with(['grupo' => fn ($grupos) => $grupos->with(['carrera', 'turno'])
            ->withCount(['inscripciones as inscritos_count' => fn ($inscripciones) => $inscripciones->inscritas()]),
            'asignatura', 'docente.persona', 'evaluaciones.tipo'])->withCount('resultados')
            ->deGrupo(isset($filtros['grupo']) ? (int) $filtros['grupo'] : null)
            ->deAsignatura(isset($filtros['asignatura']) ? (int) $filtros['asignatura'] : null)
            ->ordenados()->paginarListado();

        return view('academico.calificaciones.index', compact('materias', 'grupos', 'asignaturas', 'filtros'));
    }

    public function materia(AcaGrupoAsignatura $materia)
    {
        $usuario = auth()->user();
        $materia->load(['grupo.carrera', 'grupo.turno', 'grupo.periodo', 'asignatura', 'docente.persona']);
        $evaluaciones = $materia->evaluaciones()->with('tipo')->get();
        $inscripciones = $materia->grupo->inscripciones()->with('alumno.persona')->get();
        // Recursantes: su inscripción de origen (la del resultado reprobado), con el grupo y periodo donde reprobaron.
        $recursantes = $materia->recursamientos()->with(['origen.inscripcion.alumno.persona', 'origen.inscripcion.alumno.estado', 'origen.inscripcion.grupo.periodo'])->get()
            ->map(fn (AcaRecursamiento $recursamiento) => $recursamiento->origen->inscripcion);
        $calificaciones = TrxCalificacion::whereIn('aca_evaluacion_id', $evaluaciones->pluck('id'))
            ->whereIn('aca_inscripcion_id', $inscripciones->pluck('id')->merge($recursantes->pluck('id')))
            ->get()->groupBy('aca_inscripcion_id')->map(fn (Collection $deLaInscripcion) => $deLaInscripcion->keyBy('aca_evaluacion_id'));
        $nombreOrdenado = fn (AcaInscripcion $inscripcion) => mb_strtolower(implode(' ', [$inscripcion->alumno->persona->primer_apellido,
            $inscripcion->alumno->persona->segundo_apellido, $inscripcion->alumno->persona->nombre]));
        // INSCRITO primero; las de BAJA o CAMBIO_GRUPO solo si ya tenían calificación (en solo lectura).
        $filas = $inscripciones->filter(fn (AcaInscripcion $inscripcion) => $inscripcion->estado === AcaInscripcion::INSCRITO || $calificaciones->has($inscripcion->id))
            ->sortBy(fn (AcaInscripcion $inscripcion) => ($inscripcion->estado === AcaInscripcion::INSCRITO ? '0 ' : '1 ').$nombreOrdenado($inscripcion))
            ->values();
        $recursantes = $recursantes->sortBy($nombreOrdenado)->values();
        // Filas editables: INSCRITO y recursantes activos.
        $calificables = $materia->inscripcionesCalificables();
        $resultados = $materia->resultados()->get()->keyBy('aca_inscripcion_id');
        $permisos = [
            'capturar' => $usuario->can('capturar', $materia),
            'corregir' => $usuario->can('corregir', $materia),
            'cerrarEvaluacion' => $usuario->can('cerrarEvaluacion', $materia),
            'cerrarResultados' => $usuario->can('cerrarResultados', $materia),
        ];
        $resumen = $permisos['cerrarResultados'] ? TrxResultadoAsignatura::resumen($materia) : null;
        $reglas = TrxResultadoAsignatura::reglas();

        return view('academico.calificaciones.materia', compact('materia', 'evaluaciones', 'filas', 'recursantes', 'calificables', 'calificaciones', 'resultados', 'permisos', 'resumen', 'reglas'));
    }

    public function guardar(GuardarCalificacionesRequest $solicitud, AcaGrupoAsignatura $materia)
    {
        $usuario = $solicitud->user();
        Auditoria::motivoPorDefecto('Captura de calificaciones');
        ['guardadas' => $guardadas, 'sin_recalcular' => $sinRecalcular] = TrxCalificacion::guardarTabla($materia, $solicitud->validated('calificaciones'), $usuario,
            $usuario->can('capturar', $materia), $usuario->can('corregir', $materia), $solicitud->validated('motivo'));

        $redireccion = redirect()->route('calificaciones.materia', $materia)->with('status', match ($guardadas) {
            0 => 'No había cambios que guardar.',
            1 => 'Se guardó 1 calificación.',
            default => "Se guardaron {$guardadas} calificaciones.",
        });

        // Resultados vigentes que no se recalcularon (extraordinario, recursamiento o con recursamiento): se avisa.
        return $sinRecalcular
            ? $redireccion->withErrors(['accion' => 'Se corrigieron los parciales, pero el resultado vigente no cambió porque ya tiene extraordinario o recursamiento: '
                .implode(', ', $sinRecalcular).'. Si hace falta, corrígelo en Regularización.'])
            : $redireccion;
    }

    public function cerrarMateria(AcaGrupoAsignatura $materia)
    {
        Auditoria::motivoPorDefecto('Cierre de resultados ordinarios');
        $conteo = TrxResultadoAsignatura::cerrarMateria($materia->load(['asignatura', 'grupo']), auth()->user());

        return redirect()->route('calificaciones.materia', $materia)->with('status', "Resultados de {$materia->nombre} cerrados: ".$this->describirConteo($conteo).'.');
    }

    public function grupo(AcaGrupoEscolar $grupo)
    {
        // Cualquier periodo en consulta; cerrar resultados solo en el activo (TrxResultadoAsignatura::cerrarGrupo).
        $grupo->load(['carrera', 'turno', 'periodo']);
        $puedeCerrar = PeriodoDeTrabajo::permite(PeriodoDeTrabajo::OPERAR, $grupo->periodo);
        $materias = $grupo->materias()->with(['grupo', 'asignatura', 'docente.persona', 'evaluaciones.tipo'])->ordenados()->get();
        $resumenes = $materias->mapWithKeys(fn (AcaGrupoAsignatura $materia) => [$materia->id => TrxResultadoAsignatura::resumen($materia)]);
        $totales = ['aprobados' => $resumenes->sum('aprobados'), 'reprobados' => $resumenes->sum('reprobados'),
            'faltantes' => $resumenes->sum(fn (array $resumen) => count($resumen['faltantes'])), 'listas' => $resumenes->where('listo', true)->count()];
        $reglas = TrxResultadoAsignatura::reglas();

        return view('academico.calificaciones.grupo', compact('grupo', 'materias', 'resumenes', 'totales', 'reglas', 'puedeCerrar'));
    }

    public function cerrarGrupo(AcaGrupoEscolar $grupo)
    {
        Auditoria::motivoPorDefecto('Cierre de resultados ordinarios del grupo');
        ['cerradas' => $cerradas, 'pendientes' => $pendientes] = TrxResultadoAsignatura::cerrarGrupo($grupo, auth()->user());
        $redireccion = redirect()->route('calificaciones.grupo', $grupo);
        if ($cerradas) {
            $redireccion->with('status', 'Resultados cerrados: '.collect($cerradas)->map(fn (array $conteo, string $materia) => "{$materia} ({$this->describirConteo($conteo)})")->implode('; ').'.');
        }
        if ($pendientes) {
            $redireccion->withErrors(['accion' => 'Quedaron sin cerrar: '.collect($pendientes)->map(fn (string $motivo, string $materia) => "{$materia}: {$motivo}")->implode(' ')]);
        }

        return $redireccion;
    }

    private function describirConteo(array $conteo): string
    {
        $partes = array_filter([
            $conteo['creados'] ? $conteo['creados'].($conteo['creados'] === 1 ? ' nuevo' : ' nuevos') : null,
            $conteo['actualizados'] ? $conteo['actualizados'].($conteo['actualizados'] === 1 ? ' actualizado' : ' actualizados') : null,
            $conteo['sin_cambio'] ? $conteo['sin_cambio'].' sin cambio' : null,
            $conteo['omitidos'] ? $conteo['omitidos'].' con otra oportunidad (no se tocaron)' : null,
        ]);

        return $partes ? implode(', ', $partes) : 'sin alumnos';
    }
}
