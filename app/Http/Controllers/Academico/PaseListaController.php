<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\FechaClaseRequest;
use App\Http\Requests\Academico\GuardarListaRequest;
use App\Models\AcaGrupoAsignatura;
use App\Models\AcaHorario;
use App\Models\TrxAsistenciaClase;
use App\Support\PeriodoDeTrabajo;
use Illuminate\Support\Carbon;

/**
 * Pase de lista por clase: "Mis clases de hoy" del docente, la lista de una materia en una fecha y su guardado.
 * La materia la autoriza GrupoAsignaturaPolicy (verAsistencia / pasarLista); la fecha y el plazo, TrxAsistenciaClase.
 */
class PaseListaController extends Controller
{
    public function index(FechaClaseRequest $solicitud)
    {
        $dia = $this->dia($solicitud->validated('fecha'));
        $personal = $solicitud->user()->persona?->personal;
        $docente = $personal?->activo ? $personal : null;
        $periodo = PeriodoDeTrabajo::periodoActivo();
        $clases = $docente && $periodo
            ? AcaHorario::conDetalle()->deDocente($docente->id)->delPeriodo($periodo->id)->where('dia_semana', $dia->dayOfWeekIso)->ordenados()->get()
            : collect();
        $registradas = TrxAsistenciaClase::materiasRegistradas($clases->pluck('aca_grupo_asignatura_id')->unique()->values()->all(), $dia->toDateString());
        $esDiaDeClase = TrxAsistenciaClase::esDiaDeClase($dia);
        $dentroDelPlazo = TrxAsistenciaClase::dentroDelPlazo($dia);
        $diasParaCorregir = TrxAsistenciaClase::diasParaCorregir();

        return view('academico.pase-lista.index', compact('dia', 'docente', 'periodo', 'clases', 'registradas', 'esDiaDeClase', 'dentroDelPlazo', 'diasParaCorregir'));
    }

    public function lista(FechaClaseRequest $solicitud, AcaGrupoAsignatura $materia)
    {
        $usuario = $solicitud->user();
        $dia = $this->dia($solicitud->validated('fecha'));
        $materia->load(['grupo.carrera', 'grupo.turno', 'grupo.periodo', 'asignatura', 'docente.persona']);
        $filas = TrxAsistenciaClase::lista($materia, $dia);
        $problema = TrxAsistenciaClase::problemaDeFecha($materia, $dia);
        $modo = $usuario->can('pasarLista', $materia) ? TrxAsistenciaClase::modoDeRegistro($usuario, $materia, $dia) : null;
        $editable = $problema === null && $modo !== null;
        $guardada = $filas->contains(fn ($fila) => $fila->registro !== null);
        $pideMotivo = $modo === TrxAsistenciaClase::CORRECCION || ($guardada && ! $dia->isToday());
        $clasesDelDia = $materia->horarios()->with('espacio')->where('dia_semana', $dia->dayOfWeekIso)->orderBy('hora_inicio')->get();
        $esDocente = $materia->esDelDocente($usuario);
        $diasParaCorregir = TrxAsistenciaClase::diasParaCorregir();

        return view('academico.pase-lista.lista', compact('materia', 'dia', 'filas', 'problema', 'modo', 'editable', 'guardada', 'pideMotivo', 'clasesDelDia', 'esDocente', 'diasParaCorregir'));
    }

    public function guardar(GuardarListaRequest $solicitud, AcaGrupoAsignatura $materia)
    {
        $dia = $this->dia($solicitud->validated('fecha'));
        $cambios = TrxAsistenciaClase::registrarLista($materia, $dia, $solicitud->validated('marcas') ?? [], $solicitud->user(), $solicitud->validated('motivo'));

        return redirect()->route('pase-lista.lista', [$materia, 'fecha' => $dia->toDateString()])->with('status', match ($cambios) {
            0 => 'La lista no tenía cambios.',
            1 => 'Lista guardada: 1 registro.',
            default => "Lista guardada: {$cambios} registros.",
        });
    }

    private function dia(?string $fecha): Carbon
    {
        return $fecha ? Carbon::createFromFormat('Y-m-d', $fecha)->startOfDay() : today();
    }
}
