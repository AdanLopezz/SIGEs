<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\CopiarHorarioRequest;
use App\Http\Requests\Academico\FiltrarGruposRequest;
use App\Http\Requests\Academico\FiltrarHorarioConsultaRequest;
use App\Http\Requests\Academico\HorarioRequest;
use App\Models\AcaCarrera;
use App\Models\AcaGrupoEscolar;
use App\Models\AcaHorario;
use App\Models\AcaPeriodoEscolar;
use App\Models\AcaTurno;
use App\Models\OrgEspacioFisico;
use App\Models\PerPersonal;
use App\Support\CuadriculaHorario;
use App\Support\PeriodoDeTrabajo;
use Illuminate\Http\Request;

/**
 * Horarios: captura por grupo (horarios.administrar, periodo preparable), consulta por grupo (también asistencia.ver en sus
 * grupos), por docente y por aula, "Mi horario" del docente e imprimibles por grupo y por docente.
 */
class HorarioController extends Controller
{
    public function index(FiltrarGruposRequest $solicitud)
    {
        $filtros = $solicitud->validated();
        $filtros['periodo'] ??= PeriodoDeTrabajo::periodoConsulta()?->id;
        $grupos = AcaGrupoEscolar::with(['carrera', 'turno'])
            ->withCount(['materias', 'materias as materias_con_horario_count' => fn ($materias) => $materias->has('horarios')])
            ->delPeriodo($filtros['periodo'] ? (int) $filtros['periodo'] : null)
            ->deCarrera(isset($filtros['carrera']) ? (int) $filtros['carrera'] : null)
            ->deTurno(isset($filtros['turno']) ? (int) $filtros['turno'] : null)
            ->deSemestre(isset($filtros['semestre']) ? (int) $filtros['semestre'] : null)
            ->ordenados()->paginarListado();
        $minutos = AcaHorario::with('materia:id,aca_grupo_escolar_id')
            ->whereHas('materia', fn ($materias) => $materias->whereIn('aca_grupo_escolar_id', $grupos->pluck('id')))->get()
            ->groupBy('materia.aca_grupo_escolar_id')->map(fn ($clases) => $clases->sum('minutos'));
        $periodos = AcaPeriodoEscolar::ordenados()->get()->mapWithKeys(fn (AcaPeriodoEscolar $periodo) => [$periodo->id => $periodo->nombre_visible.' ('.$periodo->clave.')']);
        $carreras = AcaCarrera::ordenados()->pluck('nombre', 'id');
        $turnos = AcaTurno::ordenados()->pluck('nombre', 'id');
        $preparable = ! $filtros['periodo'] || PeriodoDeTrabajo::permite(PeriodoDeTrabajo::PREPARAR, AcaPeriodoEscolar::find($filtros['periodo']));

        return view('academico.horarios.index', compact('grupos', 'minutos', 'filtros', 'periodos', 'carreras', 'turnos', 'preparable'));
    }

    public function grupo(Request $solicitud, AcaGrupoEscolar $grupo)
    {
        $this->autorizarGrupo($solicitud, $grupo);
        $grupo->load(['carrera', 'turno', 'periodo', 'orientador.persona', 'tutor.persona']);
        $editable = $solicitud->user()->can('horarios.administrar') && PeriodoDeTrabajo::permite(PeriodoDeTrabajo::PREPARAR, $grupo->periodo);
        $clases = AcaHorario::conDetalle()->deGrupo($grupo->id)->ordenados()->get();
        $cuadricula = new CuadriculaHorario($clases, $grupo->turno->hora_entrada, $grupo->turno->hora_salida, $editable);
        $materias = $grupo->materias()->with(['asignatura', 'docente.persona'])->get()->sortBy('asignatura.nombre')->values();
        $datos = compact('grupo', 'editable', 'clases', 'cuadricula', 'materias');
        if ($editable) {
            // ?editar=ID edita una clase del grupo; ?dia=&inicio= viene de una celda vacía.
            $editando = $solicitud->integer('editar') ? $clases->firstWhere('id', $solicitud->integer('editar')) : null;
            $bloque = (int) config('siges.horarios.minutos_por_bloque', 50);
            $inicio = preg_match('/^\d{2}:\d{2}$/', (string) $solicitud->query('inicio')) ? $solicitud->query('inicio') : null;
            $datos += [
                'editando' => $editando,
                'propuesta' => [
                    'dia_semana' => $editando?->dia_semana ?? ($solicitud->integer('dia') ?: null),
                    'hora_inicio' => $editando?->inicio ?? $inicio,
                    'hora_fin' => $editando?->fin ?? ($inicio ? AcaHorario::aHora(min(AcaHorario::aMinutos($inicio) + $bloque, AcaHorario::aMinutos(substr($grupo->turno->hora_salida, 0, 5)))) : null),
                    'aca_grupo_asignatura_id' => $editando?->aca_grupo_asignatura_id,
                    'org_espacio_fisico_id' => $editando?->org_espacio_fisico_id,
                ],
                'espacios' => OrgEspacioFisico::activos()->ordenados()->get(),
                // Copiar: solo a un grupo sin horario, desde otro del mismo semestre y carrera (de cualquier periodo) que tenga horario.
                'origenes' => $clases->isEmpty() ? AcaGrupoEscolar::with(['periodo', 'turno'])->where('id', '<>', $grupo->id)
                    ->where('semestre', $grupo->semestre)->where('aca_carrera_id', $grupo->aca_carrera_id)
                    ->whereHas('materias.horarios')->orderByDesc('aca_periodo_escolar_id')->ordenados()->get() : collect(),
            ];
        }

        return view('academico.horarios.grupo', $datos);
    }

    public function store(HorarioRequest $solicitud, AcaGrupoEscolar $grupo)
    {
        $avisos = (new AcaHorario)->guardar($solicitud->validated());

        return redirect()->route('horarios.grupo', $grupo)->with('status', 'Clase agregada al horario.')->with('avisos', $avisos);
    }

    public function update(HorarioRequest $solicitud, AcaHorario $horario)
    {
        $avisos = $horario->guardar($solicitud->validated());

        return redirect()->route('horarios.grupo', $horario->materia->aca_grupo_escolar_id)->with('status', 'Clase actualizada.')->with('avisos', $avisos);
    }

    public function destroy(AcaHorario $horario)
    {
        $grupo = $horario->materia->aca_grupo_escolar_id;
        $horario->eliminar();

        return redirect()->route('horarios.grupo', $grupo)->with('status', 'Clase quitada del horario.');
    }

    public function copiar(CopiarHorarioRequest $solicitud, AcaGrupoEscolar $grupo)
    {
        $origen = AcaGrupoEscolar::findOrFail($solicitud->validated('origen'));
        $resultado = AcaHorario::copiar($origen, $grupo, $solicitud->validated('org_espacio_fisico_id'));
        $texto = $resultado['copiadas'] === 1 ? '1 clase copiada' : "{$resultado['copiadas']} clases copiadas";

        return redirect()->route('horarios.grupo', $grupo)
            ->with('status', "{$texto} desde {$origen->identificador}.".($resultado['omitidas'] ? ' '.count($resultado['omitidas']).' omitidas.' : ''))
            ->with('omitidas', $resultado['omitidas']);
    }

    // Horario por docente (Control Escolar).
    public function docentes(FiltrarHorarioConsultaRequest $solicitud)
    {
        $periodo = $this->periodo($solicitud);
        $docentes = PerPersonal::with('persona')->whereHas('materias', fn ($materias) => $materias->whereHas('grupo', fn ($grupos) => $grupos->where('aca_periodo_escolar_id', $periodo?->id)))
            ->ordenados()->get();
        $docente = $solicitud->validated('docente') ? $docentes->firstWhere('id', (int) $solicitud->validated('docente')) : null;
        $cuadricula = $docente ? $this->cuadriculaDeDocente($docente, $periodo) : null;

        return view('academico.horarios.consulta', ['tipo' => 'docente', 'periodo' => $periodo, 'periodos' => $this->periodos(),
            'opciones' => $docentes->mapWithKeys(fn (PerPersonal $personal) => [$personal->id => $personal->nombre]), 'elegido' => $docente,
            'titulo' => $docente?->nombre, 'cuadricula' => $cuadricula]);
    }

    // Horario por aula (Control Escolar): ocupación del aula en el periodo.
    public function aulas(FiltrarHorarioConsultaRequest $solicitud)
    {
        $periodo = $this->periodo($solicitud);
        $espacios = OrgEspacioFisico::ordenados()->get();
        $espacio = $solicitud->validated('aula') ? $espacios->firstWhere('id', (int) $solicitud->validated('aula')) : null;
        $cuadricula = $espacio ? new CuadriculaHorario(AcaHorario::conDetalle()->deEspacio($espacio->id)->delPeriodo($periodo?->id ?? 0)->ordenados()->get()) : null;

        return view('academico.horarios.consulta', ['tipo' => 'aula', 'periodo' => $periodo, 'periodos' => $this->periodos(),
            'opciones' => $espacios->mapWithKeys(fn (OrgEspacioFisico $aula) => [$aula->id => $aula->nombre.' · capacidad '.$aula->capacidad.($aula->activo ? '' : ' (inactiva)')]),
            'elegido' => $espacio, 'titulo' => $espacio ? $espacio->nombre.' · capacidad '.$espacio->capacidad : null, 'cuadricula' => $cuadricula]);
    }

    // "Mi horario": el del docente de la cuenta, en el periodo de consulta.
    public function miHorario(Request $solicitud)
    {
        $docente = $this->personalDe($solicitud);
        $periodo = PeriodoDeTrabajo::periodoConsulta();

        return view('academico.horarios.mi-horario', ['docente' => $docente, 'periodo' => $periodo,
            'cuadricula' => $docente ? $this->cuadriculaDeDocente($docente, $periodo) : null]);
    }

    public function imprimirGrupo(Request $solicitud, AcaGrupoEscolar $grupo)
    {
        $this->autorizarGrupo($solicitud, $grupo);
        $grupo->load(['carrera', 'turno', 'periodo', 'orientador.persona', 'tutor.persona']);
        $clases = AcaHorario::conDetalle()->deGrupo($grupo->id)->ordenados()->get();

        return view('academico.horarios.imprimir', ['tipo' => 'grupo', 'grupo' => $grupo, 'periodo' => $grupo->periodo,
            'titulo' => 'Grupo '.$grupo->nombre_completo.' · '.$grupo->semestre.'.º semestre',
            'cuadricula' => new CuadriculaHorario($clases, $grupo->turno->hora_entrada, $grupo->turno->hora_salida),
            'volver' => route('horarios.grupo', $grupo)]);
    }

    // Control Escolar imprime el de cualquier docente; el docente, solo el suyo.
    public function imprimirDocente(Request $solicitud, PerPersonal $docente)
    {
        $propio = $this->personalDe($solicitud)?->is($docente);
        abort_unless($solicitud->user()->can('horarios.administrar') || $propio, 403);
        $periodo = $solicitud->integer('periodo') ? AcaPeriodoEscolar::findOrFail($solicitud->integer('periodo')) : PeriodoDeTrabajo::periodoConsulta();
        $docente->load('persona');

        return view('academico.horarios.imprimir', ['tipo' => 'docente', 'grupo' => null, 'periodo' => $periodo,
            'titulo' => 'Docente '.$docente->nombre, 'cuadricula' => $this->cuadriculaDeDocente($docente, $periodo),
            'volver' => $propio ? route('mi-horario.index') : route('horarios.docentes', ['docente' => $docente->id, 'periodo' => $periodo?->id])]);
    }

    // horarios.administrar ve todos los grupos; asistencia.ver, los suyos (orientador, tutor o docente) en el periodo del grupo.
    private function autorizarGrupo(Request $solicitud, AcaGrupoEscolar $grupo): void
    {
        $usuario = $solicitud->user();
        abort_unless($usuario->can('horarios.administrar')
            || ($usuario->can('asistencia.ver') && AcaGrupoEscolar::whereKey($grupo->id)->visiblesEnAsistencia($usuario, $grupo->aca_periodo_escolar_id)->exists()), 403);
    }

    private function cuadriculaDeDocente(PerPersonal $docente, ?AcaPeriodoEscolar $periodo): CuadriculaHorario
    {
        return new CuadriculaHorario(AcaHorario::conDetalle()->deDocente($docente->id)->delPeriodo($periodo?->id ?? 0)->ordenados()->get());
    }

    private function personalDe(Request $solicitud): ?PerPersonal
    {
        $personal = $solicitud->user()->persona?->personal;

        return $personal?->activo ? $personal : null;
    }

    private function periodo(FiltrarHorarioConsultaRequest $solicitud): ?AcaPeriodoEscolar
    {
        return $solicitud->validated('periodo') ? AcaPeriodoEscolar::find($solicitud->validated('periodo')) : PeriodoDeTrabajo::periodoConsulta();
    }

    private function periodos()
    {
        return AcaPeriodoEscolar::ordenados()->get()->mapWithKeys(fn (AcaPeriodoEscolar $periodo) => [$periodo->id => $periodo->nombre_visible.' ('.$periodo->clave.')']);
    }
}
