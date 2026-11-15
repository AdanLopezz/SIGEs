<?php

namespace App\Http\Controllers\Prefectura;

use App\Http\Controllers\Controller;
use App\Http\Requests\Prefectura\CerrarDiaRequest;
use App\Http\Requests\Prefectura\FiltrarAsistenciaRequest;
use App\Http\Requests\Prefectura\JustificarAsistenciaRequest;
use App\Models\AcaGrupoEscolar;
use App\Models\TrxAsistenciaDiaria;
use App\Models\TrxRegistroAcceso;
use App\Support\Auditoria;
use App\Support\PeriodoDeTrabajo;
use Illuminate\Support\Carbon;

class AsistenciaController extends Controller
{
    public function index(FiltrarAsistenciaRequest $solicitud)
    {
        $filtros = $solicitud->validated();
        // Grupos y fechas del periodo de consulta (sin pasar de hoy); justificar y cerrar el día, solo en el activo.
        $periodo = PeriodoDeTrabajo::periodoConsulta();
        $operable = PeriodoDeTrabajo::permite(PeriodoDeTrabajo::OPERAR, $periodo);
        $fechaMinima = $periodo?->fecha_inicio->toDateString();
        $fechaMaxima = min(today()->toDateString(), $periodo?->fecha_fin->toDateString() ?? today()->toDateString());
        $fecha = $filtros['fecha'] ?? today()->toDateString();
        $fecha = Carbon::parse($fechaMinima !== null ? max($fechaMinima, min($fecha, $fechaMaxima)) : $fecha);
        $grupos = AcaGrupoEscolar::with(['carrera', 'turno'])->visiblesEnAsistencia($solicitud->user(), PeriodoDeTrabajo::idConsulta())->ordenados()->get();
        // Sin grupo elegido (o uno ajeno) se muestra el primero visible.
        $grupo = $grupos->firstWhere('id', (int) ($filtros['grupo'] ?? 0)) ?? $grupos->first();

        $filas = collect();
        $totales = array_fill_keys(array_keys(TrxAsistenciaDiaria::ESTADOS), 0) + ['SIN_REGISTRO' => 0];
        if ($grupo) {
            $inscripciones = $grupo->inscripciones()->inscritas()->with('alumno.persona')->get()->sortBy('alumno.persona.primer_apellido')->values();
            $asistencias = TrxAsistenciaDiaria::whereIn('aca_inscripcion_id', $inscripciones->pluck('id'))->whereDate('fecha', $fecha)->get()->keyBy('aca_inscripcion_id');
            $accesos = TrxRegistroAcceso::with('credencial')->deAlumnos($inscripciones->pluck('per_alumno_id')->all())->delDia($fecha)
                ->orderBy('fecha_hora')->get()->groupBy('credencial.per_alumno_id');
            foreach ($inscripciones as $inscripcion) {
                $movimientos = $accesos->get($inscripcion->per_alumno_id, collect());
                $asistencia = $asistencias->get($inscripcion->id);
                $totales[$asistencia?->estado ?? 'SIN_REGISTRO']++;
                $filas->push([
                    'inscripcion' => $inscripcion,
                    'asistencia' => $asistencia,
                    'entrada' => $movimientos->firstWhere('tipo_movimiento', TrxRegistroAcceso::ENTRADA)?->fecha_hora,
                    'salida' => $movimientos->where('tipo_movimiento', TrxRegistroAcceso::SALIDA)->last()?->fecha_hora,
                ]);
            }
        }
        $esDiaHabil = TrxAsistenciaDiaria::esDiaHabil($fecha);

        return view('prefectura.asistencia.index', compact('fecha', 'grupos', 'grupo', 'filas', 'totales', 'esDiaHabil', 'operable', 'fechaMinima', 'fechaMaxima'));
    }

    public function justificar(JustificarAsistenciaRequest $solicitud, TrxAsistenciaDiaria $asistencia)
    {
        $asistencia->justificar($solicitud->validated('motivo'));

        return back()->with('status', 'Asistencia justificada correctamente.');
    }

    public function cerrar(CerrarDiaRequest $solicitud)
    {
        $fecha = Carbon::parse($solicitud->validated('fecha'));
        if (! TrxAsistenciaDiaria::esDiaHabil($fecha)) {
            return back()->withErrors(['accion' => 'El '.$fecha->format('d/m/Y').' no es día hábil (fin de semana, día inhábil o fuera del periodo activo): no se registran faltas.']);
        }
        Auditoria::motivoPorDefecto('Cierre de asistencia del día');
        $faltas = TrxAsistenciaDiaria::cerrarDia($fecha);

        return back()->with('status', "Día {$fecha->format('d/m/Y')} cerrado: {$faltas} ".($faltas === 1 ? 'registro creado' : 'registros creados').' (falta, o justificada si una incidencia cubre el día).');
    }
}
