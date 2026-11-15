<?php

namespace App\Http\Controllers\ControlEscolar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ControlEscolar\Concerns\RespondeCuentaDeAcceso;
use App\Http\Requests\ControlEscolar\AlumnoRequest;
use App\Http\Requests\ControlEscolar\FiltrarAlumnosRequest;
use App\Models\AcaCarrera;
use App\Models\AcaGrupoEscolar;
use App\Models\AcaInscripcion;
use App\Models\AcaPeriodoEscolar;
use App\Models\AcaRecursamiento;
use App\Models\CatEstadoAlumno;
use App\Models\PerAlumno;
use App\Models\PerAlumnoSalud;
use App\Models\PerConsentimiento;
use App\Models\PerTutorAlumno;
use App\Models\TrxAsistenciaClase;
use App\Models\TrxCalificacion;
use App\Models\TrxIncidencia;
use App\Models\TrxResultadoAsignatura;
use App\Support\Auditoria;
use App\Support\CredencialesDeAcceso;
use App\Support\PeriodoDeTrabajo;

class AlumnoController extends Controller
{
    use RespondeCuentaDeAcceso;

    public function index(FiltrarAlumnosRequest $solicitud)
    {
        $filtros = $solicitud->validated();
        // Grupo, semestre y (para Orientador y Tutor) qué alumnos se ven: del periodo de consulta.
        $alumnos = PerAlumno::with(['persona', 'carrera', 'estado', 'inscripcionDeConsulta.grupo'])->visiblesPara($solicitud->user(), [PeriodoDeTrabajo::idConsulta()])
            ->buscar($filtros['buscar'] ?? null)
            ->deGrupo(isset($filtros['grupo']) ? (int) $filtros['grupo'] : null)
            ->deCarrera(isset($filtros['carrera']) ? (int) $filtros['carrera'] : null)
            ->deSemestre(isset($filtros['semestre']) ? (int) $filtros['semestre'] : null)
            ->conEstado(isset($filtros['estado']) ? (int) $filtros['estado'] : null)
            ->ordenados()->paginarListado();
        $grupos = AcaGrupoEscolar::with(['carrera', 'turno'])->delPeriodoConsulta()->ordenados()->get();
        $carreras = AcaCarrera::ordenados()->pluck('nombre', 'id');
        $estados = CatEstadoAlumno::ordenados()->pluck('nombre', 'id');

        return view('control-escolar.alumnos.index', compact('alumnos', 'filtros', 'grupos', 'carreras', 'estados'));
    }

    public function create()
    {
        return $this->formulario(new PerAlumno);
    }

    public function store(AlumnoRequest $solicitud)
    {
        Auditoria::motivoPorDefecto('Alta de alumno');
        $alumno = PerAlumno::registrar($solicitud->safe()->except('expediente'), $solicitud->file('expediente'), $solicitud->user());
        $mensaje = 'Alumno registrado e inscrito correctamente.'.($alumno->tutorReutilizado ? ' El tutor ya estaba registrado; se conservaron sus datos.' : '');

        return redirect()->route('alumnos.show', $alumno)->with('status', $mensaje);
    }

    public function show(PerAlumno $alumno)
    {
        $alumno->load(['persona.user.roles', 'carrera', 'estado', 'expediente', 'inscripcionActual.grupo.carrera', 'inscripcionActual.grupo.turno',
            'tutores' => fn ($tutores) => $tutores->with('persona.user.roles')->orderByDesc('activo')->orderByDesc('es_contacto_emergencia')->orderBy('id'),
            'inscripciones' => fn ($inscripciones) => $inscripciones->with(['grupo.periodo', 'grupo.carrera', 'grupo.turno', 'baja.registradoPor'])->orderByDesc('fecha_inscripcion')->orderByDesc('id'),
            'credencialActiva']);
        if (auth()->user()->can('verSalud', $alumno)) {
            $alumno->load('salud');
        }
        // Los avisos y la tarjeta de consentimientos solo consideran tutores activos.
        $tutoresActivos = $alumno->tutores->where('activo', true);
        $consentimientos = PerConsentimiento::matrizVigentes($tutoresActivos->pluck('id')->all());
        $otorgado = fn ($tutor, string $tipo) => (bool) ($consentimientos[$tutor->id][$tipo] ?? null)?->otorgado;
        $avisos = [
            // Tutores sin DATOS_PERSONALES vigente y otorgado.
            'sinDatosPersonales' => $tutoresActivos->reject(fn ($tutor) => $otorgado($tutor, PerConsentimiento::DATOS_PERSONALES)),
            // Ningún tutor con DATOS_SALUD vigente y otorgado.
            'sinDatosSalud' => $tutoresActivos->doesntContain(fn ($tutor) => $otorgado($tutor, PerConsentimiento::DATOS_SALUD)),
        ];
        // Usuario propuesto para "Crear cuenta" de cada tutor activo sin cuenta (al crearla se vuelve a comprobar).
        $usuariosSugeridos = auth()->user()->can('editar', $alumno)
            ? $tutoresActivos->filter(fn ($tutor) => ! $tutor->persona->user)
                ->mapWithKeys(fn ($tutor) => [$tutor->id => CredencialesDeAcceso::usuario($tutor->persona->nombre, $tutor->persona->primer_apellido)])->all()
            : [];
        $tiposConsentimiento = PerConsentimiento::TIPOS;
        $limitesBaja = $alumno->inscripcionActual ? PerAlumno::limitesFechaBaja($alumno->inscripcionActual->loadMissing('grupo.periodo')) : null;
        // Las 5 incidencias más recientes que el usuario puede ver (null si no tiene permiso de incidencias).
        // Por periodo (el de consulta): inscripción y grupo, boleta, recursamientos e incidencias. Lo demás de la ficha no cambia.
        $periodoConsulta = PeriodoDeTrabajo::periodoConsulta();
        $delPeriodo = $alumno->inscripciones->filter(fn ($inscripcion) => $inscripcion->grupo->aca_periodo_escolar_id === $periodoConsulta?->id);
        $inscripcionConsulta = $delPeriodo->firstWhere('estado', AcaInscripcion::INSCRITO) ?? $delPeriodo->first();
        $incidencias = auth()->user()->canAny(['incidencias.registrar', 'incidencias.cerrar'])
            ? TrxIncidencia::with('tipo')->visiblesPara(auth()->user())->deAlumno($alumno->id)->delPeriodo($periodoConsulta?->id)->recientes()->limit(5)->get()
            : null;
        $boleta = $inscripcionConsulta ? ['inscripcion' => $inscripcionConsulta] + $inscripcionConsulta->boleta() : null;
        // Inscribir, cambiar de grupo, dar de baja y reingresar operan sobre el periodo activo.
        $operaPeriodoActivo = ! PeriodoDeTrabajo::esConsultaHistorica();
        $inscripciones = $alumno->inscripciones->pluck('id')->all();
        // Recursamientos del periodo activo (en curso o ya cerrados) y materias que adeuda.
        $recursamientos = AcaRecursamiento::with(['materia.asignatura', 'materia.grupo', 'materia.docente.persona', 'origen.materia.grupo.periodo'])
            ->delAlumno($alumno->id)->delPeriodoConsulta()->orderBy('fecha_inicio')->get();
        AcaRecursamiento::cargarResultados($recursamientos);
        // Asistencia por materia (pase de lista por clase) del periodo de consulta.
        $asistenciaClase = $inscripcionConsulta ? TrxAsistenciaClase::resumenDelAlumno($alumno, $periodoConsulta->id) : collect();
        $adeudos = ['total' => TrxResultadoAsignatura::adeudosPorAlumno([$alumno->id])->get($alumno->id, 0), 'maximo' => TrxResultadoAsignatura::maximoReprobadas()];
        // La bitácora de salud no guarda valores, así que su historial se muestra aunque no se vea la tarjeta de salud.
        $historial = [
            ['per_persona', $alumno->per_persona_id],
            ['per_alumno_salud', $alumno->salud()->value('id')],
            ['per_tutor_alumno', $alumno->tutores->pluck('id')->all()],
            ['aca_inscripcion', $alumno->inscripciones->pluck('id')->all()],
            ['aca_baja', $alumno->inscripciones->pluck('baja.id')->filter()->all()],
            ['per_consentimiento', $alumno->consentimientos()->pluck('per_consentimiento.id')->all()],
            ['per_credencial_acceso', $alumno->credenciales()->pluck('id')->all()],
            ['trx_calificacion', TrxCalificacion::whereIn('aca_inscripcion_id', $inscripciones)->pluck('id')->all()],
            ['trx_resultado_asignatura', TrxResultadoAsignatura::whereIn('aca_inscripcion_id', $inscripciones)->pluck('id')->all()],
            ['aca_recursamiento', AcaRecursamiento::delAlumno($alumno->id)->pluck('id')->all()],
            ['trx_asistencia_clase', TrxAsistenciaClase::whereIn('aca_inscripcion_id', $inscripciones)->pluck('id')->all()],
        ];

        return view('control-escolar.alumnos.show', compact('alumno', 'historial', 'consentimientos', 'avisos', 'tiposConsentimiento', 'limitesBaja', 'incidencias', 'boleta', 'recursamientos', 'asistenciaClase', 'adeudos', 'usuariosSugeridos', 'periodoConsulta', 'inscripcionConsulta', 'operaPeriodoActivo'));
    }

    public function edit(PerAlumno $alumno)
    {
        return $this->formulario($alumno);
    }

    public function update(AlumnoRequest $solicitud, PerAlumno $alumno)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        $alumno->actualizarDatos($solicitud->safe()->except(['expediente', 'motivo']), $solicitud->file('expediente'), $solicitud->user(), $solicitud->user()->can('verSalud', $alumno));

        return redirect()->route('alumnos.show', $alumno)->with('status', 'Datos del alumno actualizados correctamente.');
    }

    public function crearCuenta(PerAlumno $alumno)
    {
        return $this->responderCuenta(fn () => $alumno->crearCuenta(), 'Alumno', route('alumnos.cuenta', $alumno));
    }

    private function formulario(PerAlumno $alumno)
    {
        $alumno->loadMissing(['persona', 'salud', 'expediente']);
        $carreras = AcaCarrera::where(fn ($consulta) => $consulta->where('activo', true)
            ->when($alumno->exists, fn ($consulta) => $consulta->orWhere('id', $alumno->aca_carrera_id)))->ordenados()->get();
        $grupos = $alumno->exists ? collect() : AcaGrupoEscolar::with(['carrera', 'turno'])->delPeriodoActivo()->ordenados()->get();
        $hayPeriodoActivo = AcaPeriodoEscolar::activo() !== null;
        $carreraBloqueada = $alumno->exists && $alumno->inscripcionActual()->exists();
        $tiposSangre = PerAlumnoSalud::TIPOS_SANGRE;
        $parentescos = PerTutorAlumno::PARENTESCOS;

        return view('control-escolar.alumnos.form', compact('alumno', 'carreras', 'grupos', 'hayPeriodoActivo', 'carreraBloqueada', 'tiposSangre', 'parentescos'));
    }
}
