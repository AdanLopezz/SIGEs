<?php

namespace App\Support;

use App\Models\AcaGrupoAsignatura;
use App\Models\AcaGrupoEscolar;
use App\Models\AcaInscripcion;
use App\Models\AcaPeriodoEscolar;
use App\Models\PerPersonal;
use App\Models\TrxResultadoAsignatura;
use App\Models\User;
use Illuminate\Database\Query\Builder as Consulta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Asistente "Cambio de periodo y reinscripción" (Control escolar → Cambio de periodo).
 * 1) origen → destino (el destino inicia después del origen y admite REINSCRIBIR: el activo o uno futuro);
 * 2) propuesta de grupos (semestre + 1, mismo identificador con el número actualizado, misma carrera y turno; un grupo
 *    que ya existe en el destino con ese identificador se reutiliza); las materias y los docentes no se copian;
 * 3) revisión de los alumnos del origen (PASA, AVISO, NO PASA, ÚLTIMO SEMESTRE, YA INSCRITO);
 * 4) confirmar(): crea los grupos y reinscribe a los marcados en una transacción. Es idempotente.
 * Lo elegido entre pasos se guarda en session(SESION) (el "borrador"); al confirmar todo se recalcula desde la base.
 */
class CambioDePeriodo
{
    public const SESION = 'cambio_periodo';

    public const SESION_RESUMEN = 'cambio_periodo_resumen';

    // Propuesta de grupo
    public const CREAR = 'CREAR';

    public const REUTILIZAR = 'REUTILIZAR';

    public const CONFLICTO = 'CONFLICTO';

    // Situación del alumno
    public const PASA = 'PASA';

    public const AVISO = 'AVISO';

    public const NO_PASA = 'NO_PASA';

    public const ULTIMO_SEMESTRE = 'ULTIMO_SEMESTRE';

    public const YA_INSCRITO = 'YA_INSCRITO';

    public const SITUACIONES = [self::PASA => 'Pasa', self::AVISO => 'Aviso', self::NO_PASA => 'No pasa',
        self::ULTIMO_SEMESTRE => 'Último semestre', self::YA_INSCRITO => 'Ya inscrito'];

    // Resultado de la confirmación
    public const CREADO = 'CREADO';

    public const REUTILIZADO = 'REUTILIZADO';

    public const REINSCRITO = 'REINSCRITO';

    public const OMITIDO = 'OMITIDO';

    public const EGRESO = 'EGRESO';

    public const RESULTADOS = [self::REINSCRITO => 'Reinscritos', self::OMITIDO => 'Omitidos', self::EGRESO => 'Candidatos a egreso'];

    private ?Collection $gruposOrigen = null;

    private ?Collection $gruposDestino = null;

    private ?Collection $propuesta = null;

    private ?Collection $revision = null;

    private ?Collection $sinCerrar = null;

    private ?array $responsablesValidos = null;

    public function __construct(public readonly AcaPeriodoEscolar $origen, public readonly AcaPeriodoEscolar $destino, private array $borrador = [], private bool $puedeCrear = true)
    {
    }

    // ── Periodos ───────────────────────────────────────────────────────────────────────────────────────────────

    // Destinos posibles de $origen: inician después de él y admiten REINSCRIBIR (el activo o uno futuro), del más cercano al más lejano.
    public static function destinosPara(AcaPeriodoEscolar $origen, ?Collection $todos = null): Collection
    {
        return ($todos ?? AcaPeriodoEscolar::ordenados()->get())
            ->filter(fn (AcaPeriodoEscolar $periodo) => $periodo->fecha_inicio->gt($origen->fecha_inicio) && PeriodoDeTrabajo::permite(PeriodoDeTrabajo::REINSCRIBIR, $periodo))
            ->sortBy('fecha_inicio')->values();
    }

    // Todos los periodos que pueden ser destino de alguno (el activo y los futuros).
    public static function destinos(): Collection
    {
        return AcaPeriodoEscolar::ordenados()->get()->filter(fn (AcaPeriodoEscolar $periodo) => PeriodoDeTrabajo::permite(PeriodoDeTrabajo::REINSCRIBIR, $periodo))
            ->sortBy('fecha_inicio')->values();
    }

    // Periodos que tienen al menos un destino (del más reciente al más antiguo).
    public static function origenes(): Collection
    {
        $todos = AcaPeriodoEscolar::ordenados()->get();

        return $todos->filter(fn (AcaPeriodoEscolar $periodo) => static::destinosPara($periodo, $todos)->isNotEmpty())->values();
    }

    public static function validarPeriodos(AcaPeriodoEscolar $origen, AcaPeriodoEscolar $destino, string $campo = 'destino'): void
    {
        if (! static::destinosPara($origen)->contains(fn (AcaPeriodoEscolar $periodo) => $periodo->is($destino))) {
            throw ValidationException::withMessages([$campo => 'El destino debe iniciar después del origen y ser el periodo activo o uno futuro.']);
        }
    }

    // ── Borrador (sesión) ─────────────────────────────────────────────────────────────────────────────────────

    public static function borradorNuevo(AcaPeriodoEscolar $origen, AcaPeriodoEscolar $destino, bool $conservar = true): array
    {
        return ['origen' => $origen->id, 'destino' => $destino->id, 'conservar' => $conservar, 'grupos' => [], 'grupos_listos' => false, 'alumnos' => [], 'revisados' => []];
    }

    // El asistente del borrador de la sesión, o null si no hay uno válido.
    public static function desdeSesion(User $usuario): ?self
    {
        $borrador = session(self::SESION);
        $origen = isset($borrador['origen']) ? AcaPeriodoEscolar::find($borrador['origen']) : null;
        $destino = isset($borrador['destino']) ? AcaPeriodoEscolar::find($borrador['destino']) : null;
        if (! $origen || ! $destino || ! static::destinosPara($origen)->contains(fn (AcaPeriodoEscolar $periodo) => $periodo->is($destino))) {
            return null;
        }

        return new self($origen, $destino, $borrador, $usuario->can('horarios.administrar'));
    }

    public function borrador(): array
    {
        return $this->borrador;
    }

    public function gruposListos(): bool
    {
        return (bool) ($this->borrador['grupos_listos'] ?? false);
    }

    public function conservaResponsables(): bool
    {
        return (bool) ($this->borrador['conservar'] ?? true);
    }

    // Paso 2: identificador, orientador y tutor de cada grupo del origen. Lo que no llega se conserva.
    public function guardarGrupos(array $grupos): void
    {
        $validos = $this->gruposOrigen()->pluck('id')->all();
        foreach ($grupos as $id => $datos) {
            if (in_array((int) $id, $validos, true)) {
                $anterior = $this->borrador['grupos'][(int) $id] ?? [];
                $this->borrador['grupos'][(int) $id] = [
                    'identificador' => mb_strtoupper(trim((string) ($datos['identificador'] ?? ''))),
                    'orientador_id' => isset($datos['orientador_id']) ? (int) $datos['orientador_id'] : ($anterior['orientador_id'] ?? null),
                    'tutor_id' => isset($datos['tutor_id']) ? (int) $datos['tutor_id'] : ($anterior['tutor_id'] ?? null),
                ];
            }
        }
        $this->reiniciar();
        $this->borrador['grupos_listos'] = $this->conflictos()->isEmpty();
    }

    // Paso 3: casilla y grupo destino de los alumnos de un grupo del origen.
    public function guardarRevision(AcaGrupoEscolar $grupo, array $alumnos): void
    {
        foreach ($this->revision()->where('grupo.id', $grupo->id)->where('elegible', true) as $fila) {
            // El formulario envía a todos (casilla con su campo oculto "0"); el que no llega conserva lo que tenía.
            if (! isset($alumnos[$fila->alumno->id]) || ! is_array($alumnos[$fila->alumno->id])) {
                continue;
            }
            $datos = $alumnos[$fila->alumno->id];
            $destino = isset($datos['destino']) ? (string) $datos['destino'] : $fila->destino;
            $this->borrador['alumnos'][$fila->alumno->id] = [
                'marcado' => filter_var($datos['marcado'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'destino' => array_key_exists($destino, $this->opcionesDestino($grupo->aca_carrera_id, $grupo->semestre + 1)) ? $destino : $fila->destino,
            ];
        }
        $this->borrador['revisados'][$grupo->id] = true;
        $this->reiniciar();
    }

    public function revisado(AcaGrupoEscolar $grupo): bool
    {
        return (bool) ($this->borrador['revisados'][$grupo->id] ?? false);
    }

    private function reiniciar(): void
    {
        $this->gruposDestino = $this->propuesta = $this->revision = null;
    }

    // ── Grupos ────────────────────────────────────────────────────────────────────────────────────────────────

    public function gruposOrigen(): Collection
    {
        return $this->gruposOrigen ??= AcaGrupoEscolar::with(['carrera', 'turno', 'orientador.persona', 'tutor.persona'])
            ->where('aca_periodo_escolar_id', $this->origen->id)->ordenados()->get();
    }

    // Grupos que ya existen en el destino, por identificador.
    public function gruposDestino(): Collection
    {
        return $this->gruposDestino ??= AcaGrupoEscolar::with(['carrera', 'turno', 'orientador.persona', 'tutor.persona'])
            ->where('aca_periodo_escolar_id', $this->destino->id)->ordenados()->get()->keyBy('identificador');
    }

    public function gruposUltimoSemestre(): Collection
    {
        return $this->gruposOrigen()->filter->esUltimoSemestre()->values();
    }

    // [ids de orientadores válidos, ids de tutores válidos]: personal activo con cuenta y ese rol.
    private function responsablesValidos(): array
    {
        return $this->responsablesValidos ??= [
            PerPersonal::conRol('Orientador')->pluck('id')->all(),
            PerPersonal::conRol('Tutor')->pluck('id')->all(),
        ];
    }

    /**
     * Un objeto por grupo del origen que no es de último semestre: origen, identificador, semestre, carrera, turno,
     * orientador_id, tutor_id, existente (grupo del destino o null), estado (CREAR, REUTILIZAR o CONFLICTO) y problema.
     */
    public function propuesta(): Collection
    {
        if ($this->propuesta) {
            return $this->propuesta;
        }
        [$orientadores, $tutores] = $this->responsablesValidos();
        $filas = $this->gruposOrigen()->reject->esUltimoSemestre()->map(function (AcaGrupoEscolar $grupo) use ($orientadores, $tutores) {
            $guardado = $this->borrador['grupos'][$grupo->id] ?? [];
            $conservar = $this->conservaResponsables();
            $semestre = $grupo->semestre + 1;

            return (object) [
                'origen' => $grupo,
                'identificador' => ($guardado['identificador'] ?? '') !== '' ? $guardado['identificador'] : AcaGrupoEscolar::identificadorPara($grupo->identificador, $semestre),
                'semestre' => $semestre,
                'carrera' => $grupo->carrera,
                'turno' => $grupo->turno,
                'orientador_id' => $guardado['orientador_id'] ?? ($conservar && in_array($grupo->orientador_id, $orientadores, true) ? $grupo->orientador_id : null),
                'tutor_id' => $guardado['tutor_id'] ?? ($conservar && in_array($grupo->tutor_id, $tutores, true) ? $grupo->tutor_id : null),
                'existente' => null,
                'estado' => self::CREAR,
                'problema' => null,
            ];
        })->values();
        $repetidos = $filas->countBy('identificador')->filter(fn (int $total) => $total > 1);
        foreach ($filas as $fila) {
            $fila->existente = $this->gruposDestino()->get($fila->identificador);
            $fila->problema = match (true) {
                ! preg_match('/^[0-9A-Z]{1,20}$/', $fila->identificador) => 'El identificador solo admite letras mayúsculas y números (máximo 20).',
                $repetidos->has($fila->identificador) => 'Otro grupo del origen propone el mismo identificador: cambia uno.',
                $fila->existente && ((int) $fila->existente->aca_carrera_id !== (int) $fila->origen->aca_carrera_id
                    || $fila->existente->semestre !== $fila->semestre || (int) $fila->existente->aca_turno_id !== (int) $fila->origen->aca_turno_id)
                    => "Ya existe {$fila->identificador} en el destino con otra carrera, semestre o turno: cambia el identificador.",
                $fila->existente !== null => null,
                ! $this->puedeCrear => 'Crear grupos requiere el permiso de Grupos (horarios.administrar).',
                ! in_array($fila->orientador_id, $orientadores, true) || ! in_array($fila->tutor_id, $tutores, true) => 'Elige un orientador y un tutor activos.',
                default => null,
            };
            $fila->estado = $fila->problema ? self::CONFLICTO : ($fila->existente ? self::REUTILIZAR : self::CREAR);
        }

        return $this->propuesta = $filas;
    }

    public function conflictos(): Collection
    {
        return $this->propuesta()->where('estado', self::CONFLICTO)->values();
    }

    // Grupos destino para un alumno de esa carrera y semestre: los propuestos sin conflicto y los que ya existen. [identificador => texto]
    public function opcionesDestino(int $carrera, int $semestre): array
    {
        $opciones = [];
        foreach ($this->propuesta()->where('estado', '!==', self::CONFLICTO) as $fila) {
            if ((int) $fila->origen->aca_carrera_id === $carrera && $fila->semestre === $semestre) {
                $opciones[$fila->identificador] = $fila->identificador.' · '.$fila->turno->nombre.($fila->existente ? '' : ' (nuevo)');
            }
        }
        foreach ($this->gruposDestino() as $identificador => $grupo) {
            if ((int) $grupo->aca_carrera_id === $carrera && $grupo->semestre === $semestre) {
                $opciones[$identificador] ??= $identificador.' · '.$grupo->turno->nombre;
            }
        }
        ksort($opciones, SORT_NATURAL);

        return $opciones;
    }

    // ── Alumnos ───────────────────────────────────────────────────────────────────────────────────────────────

    /**
     * Un objeto por alumno del origen: inscripcion, alumno, grupo (origen), situacion, motivo, adeudos, elegible,
     * marcado y destino (identificador). Las inscripciones en BAJA solo cuentan si el alumno no tiene otra INSCRITO en el origen.
     */
    public function revision(): Collection
    {
        if ($this->revision) {
            return $this->revision;
        }
        $grupos = $this->gruposOrigen()->keyBy('id');
        $inscripciones = AcaInscripcion::with(['alumno.persona', 'alumno.estado'])->whereIn('aca_grupo_escolar_id', $grupos->keys())
            ->whereIn('estado', [AcaInscripcion::INSCRITO, AcaInscripcion::BAJA])
            ->orderByRaw('CASE WHEN estado = ? THEN 0 ELSE 1 END', [AcaInscripcion::INSCRITO])->orderByDesc('id')->get()
            ->unique('per_alumno_id');
        $ids = $inscripciones->pluck('per_alumno_id')->all();
        $adeudos = TrxResultadoAsignatura::adeudosPorAlumno($ids);
        $maximo = TrxResultadoAsignatura::maximoReprobadas();
        $enDestino = AcaInscripcion::with('grupo')->inscritas()->whereIn('per_alumno_id', $ids)->delPeriodoConsulta($this->destino->id)->get()->keyBy('per_alumno_id');
        $propuestaPorOrigen = $this->propuesta()->keyBy(fn ($fila) => $fila->origen->id);

        $filas = $inscripciones->map(function (AcaInscripcion $inscripcion) use ($grupos, $adeudos, $maximo, $enDestino, $propuestaPorOrigen) {
            $grupo = $grupos[$inscripcion->aca_grupo_escolar_id];
            $alumno = $inscripcion->alumno;
            $debe = $adeudos->get($alumno->id, 0);
            [$situacion, $motivo] = match (true) {
                $enDestino->has($alumno->id) => [self::YA_INSCRITO, 'Ya inscrito en '.$enDestino[$alumno->id]->grupo->identificador.'.'],
                $inscripcion->estado === AcaInscripcion::BAJA => [self::NO_PASA, "Baja en {$grupo->identificador} (estado: {$alumno->estado->nombre})."],
                ! $alumno->estado->permite_inscripcion => [self::NO_PASA, "Estado: {$alumno->estado->nombre}."],
                $grupo->esUltimoSemestre() => [self::ULTIMO_SEMESTRE, 'Último semestre: candidato a egreso.'],
                $debe > $maximo => [self::AVISO, "Adeuda {$debe} materias (máximo {$maximo})."],
                default => [self::PASA, null],
            };
            $elegible = in_array($situacion, [self::PASA, self::AVISO], true);
            $guardado = $this->borrador['alumnos'][$alumno->id] ?? null;
            $propuesto = $propuestaPorOrigen->get($grupo->id);
            $opciones = $elegible ? $this->opcionesDestino((int) $grupo->aca_carrera_id, $grupo->semestre + 1) : [];
            $destino = $guardado['destino'] ?? null;
            if (! array_key_exists((string) $destino, $opciones)) {
                $destino = $propuesto && array_key_exists($propuesto->identificador, $opciones) ? $propuesto->identificador : array_key_first($opciones);
            }

            return (object) [
                'inscripcion' => $inscripcion,
                'alumno' => $alumno,
                'grupo' => $grupo,
                'situacion' => $situacion,
                'motivo' => $motivo,
                'adeudos' => $debe,
                'elegible' => $elegible,
                'marcado' => $elegible && ($guardado['marcado'] ?? $situacion === self::PASA),
                'destino' => $elegible ? $destino : null,
                'opciones' => $opciones,
            ];
        });
        $orden = $grupos->keys()->flip();

        return $this->revision = $filas->sortBy([fn ($a, $b) => $orden[$a->grupo->id] <=> $orden[$b->grupo->id],
            fn ($a, $b) => strnatcasecmp($a->alumno->persona->nombre_completo, $b->alumno->persona->nombre_completo)])->values();
    }

    // [grupo del origen => materias con algún alumno INSCRITO sin resultado cerrado]. Solo los grupos que se reinscriben.
    public function materiasSinCerrar(): Collection
    {
        return $this->sinCerrar ??= AcaGrupoAsignatura::whereIn('aca_grupo_escolar_id', $this->gruposOrigen()->reject->esUltimoSemestre()->pluck('id'))
            ->whereExists(fn (Consulta $inscripciones) => $inscripciones->selectRaw('1')->from('aca_inscripcion as pendiente')
                ->whereColumn('pendiente.aca_grupo_escolar_id', 'aca_grupo_asignatura.aca_grupo_escolar_id')
                ->where('pendiente.estado', AcaInscripcion::INSCRITO)
                ->whereNotExists(fn (Consulta $resultados) => $resultados->selectRaw('1')->from('trx_resultado_asignatura as cerrado')
                    ->whereColumn('cerrado.aca_inscripcion_id', 'pendiente.id')->whereColumn('cerrado.aca_grupo_asignatura_id', 'aca_grupo_asignatura.id')))
            ->groupBy('aca_grupo_escolar_id')->selectRaw('aca_grupo_escolar_id, count(*) as total')->pluck('total', 'aca_grupo_escolar_id')
            ->map(fn ($total) => (int) $total);
    }

    // Paso 3: una fila por grupo del origen con sus conteos.
    public function resumenPorGrupo(): Collection
    {
        $propuesta = $this->propuesta()->keyBy(fn ($fila) => $fila->origen->id);
        $revision = $this->revision()->groupBy(fn ($fila) => $fila->grupo->id);
        $sinCerrar = $this->materiasSinCerrar();

        return $this->gruposOrigen()->map(fn (AcaGrupoEscolar $grupo) => (object) [
            'grupo' => $grupo,
            'destino' => $propuesta->get($grupo->id)?->identificador,
            'alumnos' => $revision->get($grupo->id, collect())->count(),
            'situaciones' => collect(array_keys(self::SITUACIONES))->mapWithKeys(fn (string $situacion) => [$situacion => $revision->get($grupo->id, collect())->where('situacion', $situacion)->count()]),
            'marcados' => $revision->get($grupo->id, collect())->where('marcado', true)->count(),
            'sin_cerrar' => $sinCerrar->get($grupo->id, 0),
            'revisado' => $this->revisado($grupo),
        ])->values();
    }

    // ── Confirmación ──────────────────────────────────────────────────────────────────────────────────────────

    public function motivo(): string
    {
        return "Reinscripción {$this->origen->nombre_visible} → {$this->destino->nombre_visible}";
    }

    /**
     * Crea (o reutiliza) los grupos y reinscribe a los marcados, todo en una transacción, con el motivo
     * "Reinscripción {origen} → {destino}". Idempotente: los grupos existentes se reutilizan y quien ya está inscrito
     * en el destino se omite. Un alumno que no pasa la validación se omite con su motivo; un error inesperado revierte todo.
     */
    public function confirmar(User $usuario, bool $aceptaPendientes): array
    {
        return DB::transaction(function () use ($usuario, $aceptaPendientes) {
            // Serializa dos confirmaciones simultáneas.
            DB::select('SELECT pg_advisory_xact_lock(17423, 2)');
            $this->puedeCrear = $usuario->can('horarios.administrar');
            $this->reiniciar();
            $this->sinCerrar = null;
            static::validarPeriodos($this->origen, $this->destino, 'accion');
            PeriodoDeTrabajo::exigir(PeriodoDeTrabajo::REINSCRIBIR, $this->destino);
            if ($this->conflictos()->isNotEmpty()) {
                throw ValidationException::withMessages(['accion' => 'Hay grupos con conflictos: '.$this->conflictos()->pluck('identificador')->implode(', ').'. Corrígelos en el paso 2.']);
            }
            if ($this->materiasSinCerrar()->sum() > 0 && ! $aceptaPendientes) {
                throw ValidationException::withMessages(['acepto_pendientes' => 'Confirma que entiendes que hay materias sin cerrar.']);
            }
            Auditoria::motivo($this->motivo());

            $grupos = [];
            $acciones = [];
            foreach ($this->propuesta() as $fila) {
                if ($fila->existente) {
                    $grupo = $fila->existente;
                    $acciones[$fila->identificador] = self::REUTILIZADO;
                } else {
                    $grupo = new AcaGrupoEscolar;
                    $grupo->guardar([
                        'aca_periodo_escolar_id' => $this->destino->id,
                        'aca_carrera_id' => $fila->origen->aca_carrera_id,
                        'aca_turno_id' => $fila->origen->aca_turno_id,
                        'identificador' => $fila->identificador,
                        'semestre' => $fila->semestre,
                        'orientador_id' => $fila->orientador_id,
                        'tutor_id' => $fila->tutor_id,
                    ]);
                    $acciones[$fila->identificador] = self::CREADO;
                }
                $grupos[$fila->identificador] = ['grupo' => $grupo, 'origen' => $fila->origen->identificador];
            }
            foreach ($this->gruposDestino() as $identificador => $grupo) {
                $grupos[$identificador] ??= ['grupo' => $grupo, 'origen' => null];
            }

            $alumnos = [];
            foreach ($this->revision() as $fila) {
                [$resultado, $motivo, $destino] = match (true) {
                    $fila->situacion === self::ULTIMO_SEMESTRE => [self::EGRESO, $fila->motivo, null],
                    ! $fila->elegible => [self::OMITIDO, $fila->motivo, null],
                    ! $fila->marcado => [self::OMITIDO, trim(($fila->motivo ?? '').' No se marcó para reinscribir.'), null],
                    ! isset($grupos[$fila->destino]) => [self::OMITIDO, 'Sin grupo destino.', null],
                    default => [null, null, $fila->destino],
                };
                if ($resultado === null) {
                    try {
                        $fila->alumno->reinscribir($grupos[$destino]['grupo'], $this->destino->fecha_inicio);
                        $resultado = self::REINSCRITO;
                    } catch (ValidationException $excepcion) {
                        [$resultado, $motivo, $destino] = [self::OMITIDO, collect($excepcion->errors())->flatten()->first(), null];
                    }
                }
                $alumnos[] = [
                    'alumno_id' => $fila->alumno->id,
                    'matricula' => $fila->alumno->matricula,
                    'nombre' => $fila->alumno->persona->nombre_completo,
                    'origen' => $fila->grupo->identificador,
                    'destino' => $destino,
                    'resultado' => $resultado,
                    'motivo' => $motivo,
                ];
            }
            $reinscritosPorGrupo = collect($alumnos)->where('resultado', self::REINSCRITO)->countBy('destino');

            return [
                'origen' => ['nombre' => $this->origen->nombre_visible, 'clave' => $this->origen->clave],
                'destino' => ['nombre' => $this->destino->nombre_visible, 'clave' => $this->destino->clave],
                'fecha_inscripcion' => $this->destino->fecha_inicio->format('d/m/Y'),
                'confirmado' => now()->format('d/m/Y H:i'),
                'usuario' => $usuario->name,
                'motivo' => $this->motivo(),
                'grupos' => collect($acciones)->map(fn (string $accion, string $identificador) => [
                    'identificador' => $identificador,
                    'origen' => $grupos[$identificador]['origen'],
                    'semestre' => $grupos[$identificador]['grupo']->semestre,
                    'carrera' => $grupos[$identificador]['grupo']->carrera()->value('nombre'),
                    'turno' => $grupos[$identificador]['grupo']->turno()->value('nombre'),
                    'accion' => $accion,
                    'alumnos' => $reinscritosPorGrupo->get($identificador, 0),
                ])->values()->all(),
                'alumnos' => $alumnos,
            ];
        });
    }
}
