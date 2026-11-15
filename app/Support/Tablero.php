<?php

namespace App\Support;

use App\Models\AcaBaja;
use App\Models\AcaEvaluacion;
use App\Models\AcaGrupoAsignatura;
use App\Models\AcaGrupoEscolar;
use App\Models\AcaHorario;
use App\Models\AcaInscripcion;
use App\Models\AcaPeriodoEscolar;
use App\Models\TrxAsistenciaClase;
use App\Models\TrxAsistenciaDiaria;
use App\Models\TrxIncidencia;
use App\Models\TrxPermisoSalida;
use App\Models\TrxRegistroAcceso;
use App\Models\TrxResultadoAsignatura;
use App\Models\TrxSeguimientoAlumno;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as Consulta;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Tablero del inicio por rol (personal). Una sección por grupo de roles, sin repetir:
 * Dirección (Administrador, Director, Subdirector), Control Escolar, Orientación y tutoría (Orientador, Tutor), Docente y Prefectura.
 * Las cifras del periodo usan el periodo de consulta; las de "hoy", solo cuando se consulta el periodo activo.
 * Tarjeta: ['titulo', 'valor', 'detalle', 'icono', 'tono' (exito|aviso|peligro|info|neutro), 'url' (null = sin enlace), 'lista' => [[etiqueta, valor]]].
 */
class Tablero
{
    public const SECCIONES = [
        'direccion' => ['titulo' => 'Dirección', 'icono' => 'bi-building', 'roles' => ['Administrador', 'Director', 'Subdirector']],
        'control_escolar' => ['titulo' => 'Control Escolar', 'icono' => 'bi-mortarboard', 'roles' => ['Control Escolar']],
        'orientacion' => ['titulo' => 'Orientación y tutoría', 'icono' => 'bi-person-heart', 'roles' => ['Orientador', 'Tutor']],
        'docente' => ['titulo' => 'Docencia', 'icono' => 'bi-easel', 'roles' => ['Docente']],
        'prefectura' => ['titulo' => 'Prefectura', 'icono' => 'bi-door-open', 'roles' => ['Prefectura']],
    ];

    // Cifras que se calculan con varias consultas: se guardan unos minutos por usuario y periodo.
    public const MINUTOS_CACHE = 5;

    private ?AcaPeriodoEscolar $periodo;

    private bool $esActivo;

    public function __construct(private User $usuario)
    {
        $this->periodo = PeriodoDeTrabajo::periodoConsulta();
        $this->esActivo = $this->periodo !== null && ! PeriodoDeTrabajo::esConsultaHistorica();
    }

    // [clave => ['titulo', 'icono', 'tarjetas', 'aviso']] solo de las secciones de los roles de la cuenta.
    public static function para(User $usuario): array
    {
        return (new static($usuario))->secciones();
    }

    public function secciones(): array
    {
        $secciones = [];
        foreach (self::SECCIONES as $clave => $seccion) {
            if (! $this->usuario->hasAnyRole($seccion['roles'])) {
                continue;
            }
            $secciones[$clave] = ['titulo' => $seccion['titulo'], 'icono' => $seccion['icono'],
                'aviso' => $this->periodo ? null : 'No hay un periodo escolar activo.',
                'tarjetas' => $this->periodo ? array_values(array_filter($this->{'tarjetas'.str_replace('_', '', ucwords($clave, '_'))}())) : []];
        }

        return $secciones;
    }

    // ── Secciones ────────────────────────────────────────────────────────────

    private function tarjetasDireccion(): array
    {
        $datos = $this->recordar('direccion', function () {
            $grupos = $this->gruposDelPeriodo();
            $porCarrera = $this->inscritas($grupos)->join('aca_grupo_escolar as g', 'g.id', '=', 'aca_inscripcion.aca_grupo_escolar_id')
                ->join('aca_carrera as c', 'c.id', '=', 'g.aca_carrera_id')->groupBy('c.nombre')->orderBy('c.nombre')
                ->selectRaw('c.nombre as etiqueta, count(*) as total')->pluck('total', 'etiqueta')->map(fn ($total) => (int) $total)->all();
            $resultados = TrxResultadoAsignatura::whereIn('aca_grupo_asignatura_id', AcaGrupoAsignatura::whereIn('aca_grupo_escolar_id', $grupos)->select('id'))
                ->selectRaw('count(*) as total')->selectRaw("count(*) filter (where estado = 'REPROBADA') as reprobadas")->toBase()->first();
            // El Director no tiene seguimiento.administrar: ve todos los casos en cifras, sin enlace.
            $casos = $this->usuario->can('seguimiento.administrar') ? TrxSeguimientoAlumno::visiblesPara($this->usuario) : TrxSeguimientoAlumno::query();
            $casos = $casos->delPeriodo($this->periodo->id)->abiertos()
                ->selectRaw('count(*) as abiertos')->selectRaw('count(*) filter (where riesgo_abandono) as en_riesgo')->toBase()->first();

            return [
                'porCarrera' => $porCarrera,
                'resultados' => [(int) $resultados->total, (int) $resultados->reprobadas],
                'bajas' => $this->bajas($grupos),
                'incidencias' => $this->incidenciasAbiertas($grupos),
                'casos' => [(int) $casos->abiertos, (int) $casos->en_riesgo],
            ];
        });
        [$resultados, $reprobadas] = $datos['resultados'];
        [$abiertos, $enRiesgo] = $datos['casos'];
        $tarjetas = [
            $this->tarjeta('Inscritos', array_sum($datos['porCarrera']), 'Alumnos inscritos en el periodo', 'bi-people', 'info', $this->enlace('alumnos.index', 'alumnos.ver'),
                collect($datos['porCarrera'])->map(fn ($total, $carrera) => [$carrera, $total])->values()->all()),
            $this->esActivo ? $this->asistenciaDeHoy($this->gruposDelPeriodo()) : null,
            $this->tarjeta('Materias reprobadas', $reprobadas, $resultados ? 'de '.$resultados.' resultados cerrados ('.$this->porcentaje($reprobadas, $resultados).')' : 'Sin resultados cerrados',
                'bi-clipboard-x', $reprobadas ? 'peligro' : 'exito', $this->enlace('regularizacion.index', 'recursamientos.administrar')),
            $this->tarjetaBajas($datos['bajas']),
            $this->tarjeta('Incidencias abiertas', $datos['incidencias'], 'Abiertas o en seguimiento', 'bi-exclamation-triangle', $datos['incidencias'] ? 'aviso' : 'exito',
                $this->enlace('incidencias.index', ['incidencias.registrar', 'incidencias.cerrar'])),
            $this->tarjeta('Casos de seguimiento', $abiertos, 'Abiertos o en seguimiento', 'bi-person-heart', $abiertos ? 'info' : 'neutro', $this->enlace('seguimiento.index', 'seguimiento.administrar')),
            $this->tarjeta('Riesgo de abandono', $enRiesgo, 'Casos sin cerrar marcados en riesgo', 'bi-exclamation-octagon', $enRiesgo ? 'peligro' : 'exito',
                $this->enlace('seguimiento.index', 'seguimiento.administrar', ['riesgo' => 1])),
        ];

        return $tarjetas;
    }

    private function tarjetasControlEscolar(): array
    {
        $datos = $this->recordar('control_escolar', function () {
            $grupos = $this->gruposDelPeriodo();
            $porGrupo = $this->inscritas($grupos)->join('aca_grupo_escolar as g', 'g.id', '=', 'aca_inscripcion.aca_grupo_escolar_id')
                ->groupBy('g.identificador')->orderByDesc(DB::raw('count(*)'))->orderBy('g.identificador')
                ->selectRaw('g.identificador as etiqueta, count(*) as total')->pluck('total', 'etiqueta')->map(fn ($total) => (int) $total)->all();
            $sinCredencial = $this->inscritas($grupos)->whereNotExists(fn (Consulta $credenciales) => $credenciales->selectRaw('1')->from('per_credencial_acceso as cr')
                ->whereColumn('cr.per_alumno_id', 'aca_inscripcion.per_alumno_id')->where('cr.activa', true))->distinct()->count('aca_inscripcion.per_alumno_id');
            // Materias con algún INSCRITO sin resultado (misma regla que CambioDePeriodo::materiasSinCerrar).
            $sinCerrar = AcaGrupoAsignatura::whereIn('aca_grupo_escolar_id', $grupos)
                ->whereExists(fn (Consulta $inscripciones) => $inscripciones->selectRaw('1')->from('aca_inscripcion as pendiente')
                    ->whereColumn('pendiente.aca_grupo_escolar_id', 'aca_grupo_asignatura.aca_grupo_escolar_id')->where('pendiente.estado', AcaInscripcion::INSCRITO)
                    ->whereNotExists(fn (Consulta $resultados) => $resultados->selectRaw('1')->from('trx_resultado_asignatura as cerrado')
                        ->whereColumn('cerrado.aca_inscripcion_id', 'pendiente.id')->whereColumn('cerrado.aca_grupo_asignatura_id', 'aca_grupo_asignatura.id')))
                ->count();

            return [
                'porGrupo' => $porGrupo,
                'bajas' => $this->bajas($grupos),
                'sinCredencial' => $sinCredencial,
                'sinCerrar' => $sinCerrar,
                'pendientes' => TrxResultadoAsignatura::deSituacion(TrxResultadoAsignatura::PENDIENTE)->delPeriodo($this->periodo->id)->count(),
            ];
        });
        $lista = collect($datos['porGrupo'])->take(5)->map(fn ($total, $grupo) => [$grupo, $total])->values()->all();

        return [
            $this->tarjeta('Inscritos por grupo', array_sum($datos['porGrupo']), count($datos['porGrupo']).' grupos con alumnos'.(count($datos['porGrupo']) > 5 ? ' (los 5 mayores)' : ''),
                'bi-people', 'info', $this->enlace('alumnos.index', 'alumnos.ver'), $lista),
            $this->tarjetaBajas($datos['bajas']),
            $this->tarjeta('Sin credencial activa', $datos['sinCredencial'], 'Alumnos inscritos sin credencial QR activa', 'bi-qr-code', $datos['sinCredencial'] ? 'aviso' : 'exito',
                $this->enlace('alumnos.index', 'alumnos.ver')),
            $this->tarjeta('Materias sin cerrar', $datos['sinCerrar'], 'Con alumnos inscritos sin resultado', 'bi-clipboard-data', $datos['sinCerrar'] ? 'aviso' : 'exito',
                $this->enlace('calificaciones.index', ['calificaciones.capturar', 'calificaciones.corregir', 'resultados.cerrar'])),
            $this->tarjeta('Regularizaciones pendientes', $datos['pendientes'], 'Reprobadas sin recursamiento', 'bi-arrow-repeat', $datos['pendientes'] ? 'aviso' : 'exito',
                $this->enlace('regularizacion.index', 'recursamientos.administrar')),
        ];
    }

    private function tarjetasOrientacion(): array
    {
        $grupos = AcaGrupoEscolar::acompanadosPor($this->usuario, $this->periodo->id)->pluck('aca_grupo_escolar.id')->all();
        $datos = $this->recordar('orientacion', function () use ($grupos) {
            $casos = TrxSeguimientoAlumno::visiblesPara($this->usuario)->delPeriodo($this->periodo->id)->abiertos()
                ->selectRaw('count(*) as abiertos')->selectRaw('count(*) filter (where riesgo_abandono) as en_riesgo')->toBase()->first();

            return [
                'incidencias' => $this->incidenciasAbiertas($grupos, true),
                'casos' => [(int) $casos->abiertos, (int) $casos->en_riesgo],
                'atender' => $this->usuario->can('seguimiento.administrar') ? AlumnosAAtender::para($this->usuario, $this->periodo->id)->count() : null,
            ];
        });
        [$abiertos, $enRiesgo] = $datos['casos'];

        return [
            $this->esActivo ? $this->faltasDeHoy($grupos) : null,
            $this->esActivo ? $this->retardosDeHoy($grupos, $this->enlace('asistencia.index', 'asistencia.ver')) : null,
            $this->tarjeta('Incidencias abiertas', $datos['incidencias'], 'De los alumnos de tus grupos', 'bi-exclamation-triangle', $datos['incidencias'] ? 'aviso' : 'exito',
                $this->enlace('incidencias.index', ['incidencias.registrar', 'incidencias.cerrar'])),
            $this->tarjeta('Casos de seguimiento', $abiertos, $enRiesgo ? $enRiesgo.' en riesgo de abandono' : 'Abiertos o en seguimiento', 'bi-person-heart', $enRiesgo ? 'peligro' : ($abiertos ? 'info' : 'neutro'),
                $this->enlace('seguimiento.index', 'seguimiento.administrar')),
            $datos['atender'] === null ? null : $this->tarjeta('Alumnos a atender', $datos['atender'], 'Bajo el mínimo de asistencia, con adeudos o incidencias graves', 'bi-bell',
                $datos['atender'] ? 'aviso' : 'exito', $this->enlace('seguimiento.alumnos', 'seguimiento.administrar')),
        ];
    }

    private function tarjetasDocente(): array
    {
        $personal = $this->usuario->persona?->personal;
        if (! $personal?->activo) {
            return [];
        }
        $tarjetas = [];
        if ($this->esActivo) {
            $hoy = today();
            $materias = TrxAsistenciaClase::esDiaDeClase($hoy)
                ? AcaHorario::deDocente($personal->id)->delPeriodo($this->periodo->id)->where('dia_semana', $hoy->dayOfWeekIso)->distinct()->pluck('aca_grupo_asignatura_id')->all()
                : [];
            $pendientes = count($materias) - count(TrxAsistenciaClase::materiasRegistradas($materias, $hoy->toDateString()));
            $tarjetas[] = $this->tarjeta('Clases de hoy', count($materias), $materias ? $pendientes.' '.($pendientes === 1 ? 'lista pendiente' : 'listas pendientes') : 'Sin clases hoy según tu horario',
                'bi-list-check', $pendientes ? 'aviso' : 'exito', $this->enlace('pase-lista.index', 'asistencia_clase.registrar'));
        }
        // Evaluaciones abiertas de mis materias del periodo con algún INSCRITO sin calificación.
        $evaluaciones = AcaEvaluacion::where('cerrada', false)
            ->whereHas('materia', fn (Builder $materias) => $materias->where('docente_id', $personal->id)
                ->whereHas('grupo', fn (Builder $grupos) => $grupos->where('aca_periodo_escolar_id', $this->periodo->id)))
            ->selectRaw('count(*) as abiertas')
            ->selectRaw('count(*) filter (where exists (select 1 from aca_inscripcion i join aca_grupo_asignatura m on m.aca_grupo_escolar_id = i.aca_grupo_escolar_id
                where m.id = aca_evaluacion.aca_grupo_asignatura_id and i.estado = ? and not exists (select 1 from trx_calificacion c
                where c.aca_inscripcion_id = i.id and c.aca_evaluacion_id = aca_evaluacion.id))) as por_capturar', [AcaInscripcion::INSCRITO])
            ->toBase()->first();
        $porCapturar = (int) $evaluaciones->por_capturar;
        $tarjetas[] = $this->tarjeta('Evaluaciones por capturar', $porCapturar, (int) $evaluaciones->abiertas.' evaluaciones abiertas en tus materias', 'bi-pencil-square',
            $porCapturar ? 'aviso' : 'exito', $this->enlace('calificaciones.index', 'calificaciones.capturar'));

        return $tarjetas;
    }

    private function tarjetasPrefectura(): array
    {
        if (! $this->esActivo) {
            return [];
        }
        $hoy = today();
        $entradas = TrxRegistroAcceso::delDia($hoy)->where('tipo_movimiento', TrxRegistroAcceso::ENTRADA)->distinct()->count('per_credencial_acceso_id');
        $permisos = TrxPermisoSalida::where('estado', TrxPermisoSalida::PENDIENTE)->whereDate('salida_prevista_at', $hoy)->count();

        return [
            $this->tarjeta('Entradas de hoy', $entradas, 'Alumnos que registraron entrada', 'bi-qr-code-scan', 'info', $this->enlace('acceso.index', 'acceso.registrar')),
            $this->retardosDeHoy($this->gruposDelPeriodo(), $this->enlace('asistencia.index', 'asistencia.ver')),
            $this->tarjeta('Permisos pendientes de hoy', $permisos, 'Salidas de hoy sin confirmar por el tutor', 'bi-door-open', $permisos ? 'aviso' : 'exito',
                $this->enlace('permisos-salida.index', 'permisos_salida.administrar')),
        ];
    }

    // ── Cifras comunes ───────────────────────────────────────────────────────

    private function gruposDelPeriodo(): array
    {
        return AcaGrupoEscolar::where('aca_periodo_escolar_id', $this->periodo->id)->pluck('id')->all();
    }

    private function inscritas(array $grupos): Builder
    {
        return AcaInscripcion::query()->where('aca_inscripcion.estado', AcaInscripcion::INSCRITO)->whereIn('aca_inscripcion.aca_grupo_escolar_id', $grupos);
    }

    private function bajas(array $grupos): array
    {
        $bajas = AcaBaja::whereHas('inscripcion', fn (Builder $inscripciones) => $inscripciones->whereIn('aca_grupo_escolar_id', $grupos))
            ->selectRaw("count(*) filter (where tipo = 'TEMPORAL') as temporales")->selectRaw("count(*) filter (where tipo = 'DEFINITIVA') as definitivas")->toBase()->first();

        return [(int) $bajas->temporales, (int) $bajas->definitivas];
    }

    private function tarjetaBajas(array $bajas): array
    {
        [$temporales, $definitivas] = $bajas;

        return $this->tarjeta('Bajas del periodo', $temporales + $definitivas, $temporales.' temporales · '.$definitivas.' definitivas', 'bi-person-dash',
            $temporales + $definitivas ? 'aviso' : 'exito', $this->enlace('bajas.index', 'bajas.registrar'));
    }

    // $soloVisibles: además, TrxIncidencia::visiblesPara (los Orientadores y Tutores).
    private function incidenciasAbiertas(array $grupos, bool $soloVisibles = false): int
    {
        return TrxIncidencia::query()->when($soloVisibles, fn (Builder $consulta) => $consulta->visiblesPara($this->usuario))
            ->whereIn('aca_inscripcion_id', AcaInscripcion::whereIn('aca_grupo_escolar_id', $grupos)->select('id'))
            ->where('estado', '<>', TrxIncidencia::CERRADA)->count();
    }

    // Estados de hoy de los INSCRITO de $grupos: [inscritos, presentes, retardos, faltas, justificadas].
    private function estadosDeHoy(array $grupos): array
    {
        $fila = $this->inscritas($grupos)
            ->leftJoin('trx_asistencia_diaria as hoy', fn ($union) => $union->on('hoy.aca_inscripcion_id', '=', 'aca_inscripcion.id')->whereDate('hoy.fecha', today()))
            ->selectRaw('count(*) as inscritos')
            ->selectRaw("count(*) filter (where hoy.estado = 'PRESENTE') as presentes")
            ->selectRaw("count(*) filter (where hoy.estado = 'RETARDO') as retardos")
            ->selectRaw("count(*) filter (where hoy.estado = 'JUSTIFICADA') as justificadas")
            ->toBase()->first();

        return array_map('intval', (array) $fila);
    }

    private function asistenciaDeHoy(array $grupos): array
    {
        if (! TrxAsistenciaDiaria::esDiaHabil(today())) {
            return $this->tarjeta('Asistencia de hoy', '—', 'Hoy no es día hábil', 'bi-calendar-check', 'neutro', $this->enlace('asistencia.index', 'asistencia.ver'));
        }
        $hoy = $this->estadosDeHoy($grupos);
        $considerados = $hoy['inscritos'] - $hoy['justificadas'];
        $asistieron = $hoy['presentes'] + $hoy['retardos'];
        $porcentaje = $considerados > 0 ? round(100 * $asistieron / $considerados, 1) : null;

        return $this->tarjeta('Asistencia de hoy', $porcentaje === null ? '—' : TrxAsistenciaClase::formatear($porcentaje),
            $asistieron.' de '.$considerados.' con entrada ('.$hoy['retardos'].' retardos)', 'bi-calendar-check',
            $porcentaje === null ? 'neutro' : ($porcentaje >= TrxAsistenciaClase::minimoPorcentaje() ? 'exito' : 'aviso'), $this->enlace('asistencia.index', 'asistencia.ver'));
    }

    // Antes del cierre del día (21:00) no hay filas FALTA: cuenta a los inscritos sin entrada ni justificación.
    private function faltasDeHoy(array $grupos): ?array
    {
        if (! TrxAsistenciaDiaria::esDiaHabil(today())) {
            return null;
        }
        $hoy = $this->estadosDeHoy($grupos);
        $sinEntrada = $hoy['inscritos'] - $hoy['presentes'] - $hoy['retardos'] - $hoy['justificadas'];

        return $this->tarjeta('Faltas de hoy', $sinEntrada, 'Alumnos de tus grupos sin entrada registrada', 'bi-calendar-x', $sinEntrada ? 'aviso' : 'exito',
            $this->enlace('asistencia.index', 'asistencia.ver'));
    }

    private function retardosDeHoy(array $grupos, ?string $url): ?array
    {
        if (! TrxAsistenciaDiaria::esDiaHabil(today())) {
            return null;
        }
        $retardos = $this->estadosDeHoy($grupos)['retardos'];

        return $this->tarjeta('Retardos de hoy', $retardos, 'Entradas después de la tolerancia', 'bi-alarm', $retardos ? 'aviso' : 'exito', $url);
    }

    // ── Utilidades ───────────────────────────────────────────────────────────

    private function tarjeta(string $titulo, int|string $valor, ?string $detalle, string $icono, string $tono, ?string $url, array $lista = []): array
    {
        return compact('titulo', 'valor', 'detalle', 'icono', 'tono', 'url', 'lista');
    }

    // URL solo si la ruta existe y el usuario tiene alguno de los permisos.
    private function enlace(string $ruta, string|array $permisos, array $parametros = []): ?string
    {
        return Route::has($ruta) && $this->usuario->canAny((array) $permisos) ? route($ruta, $parametros) : null;
    }

    private function porcentaje(int $parte, int $total): string
    {
        return TrxAsistenciaClase::formatear($total > 0 ? round(100 * $parte / $total, 1) : null);
    }

    private function recordar(string $seccion, callable $calcular): array
    {
        return Cache::remember('tablero.'.$seccion.'.'.$this->usuario->id.'.'.$this->periodo->id, now()->addMinutes(self::MINUTOS_CACHE), $calcular);
    }
}
