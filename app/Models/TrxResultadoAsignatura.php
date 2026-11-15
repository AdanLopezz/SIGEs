<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as Consulta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Support\PeriodoDeTrabajo;

/**
 * Resultado de un alumno en una materia. Hay UNA fila por inscripción y materia (UNIQUE): guarda el resultado vigente.
 * - El cierre ordinario la crea con oportunidad ORDINARIO.
 * - El extraordinario (uno solo por materia, sobre un ORDINARIO REPROBADA) actualiza esa misma fila; la definitiva
 *   ordinaria queda en la bitácora y se puede recalcular de los parciales, que no se borran.
 * - El recursamiento (AcaRecursamiento) se califica en otra materia con la inscripción de origen, y su cierre crea
 *   otra fila (inscripción de origen, materia destino) con oportunidad RECURSAMIENTO.
 */
class TrxResultadoAsignatura extends Model
{
    use Auditable;

    public const ORDINARIO = 'ORDINARIO';

    public const EXTRAORDINARIO = 'EXTRAORDINARIO';

    public const RECURSAMIENTO = 'RECURSAMIENTO';

    public const OPORTUNIDADES = [self::ORDINARIO => 'Ordinario', self::EXTRAORDINARIO => 'Extraordinario', self::RECURSAMIENTO => 'Recursamiento'];

    public const APROBADA = 'APROBADA';

    public const REPROBADA = 'REPROBADA';

    // Situación de un resultado REPROBADA (Regularización). Cada uno tiene a lo más un recursamiento.
    public const PENDIENTE = 'PENDIENTE';

    public const EN_RECURSAMIENTO = 'EN_RECURSAMIENTO';

    public const REGULARIZADA = 'REGULARIZADA';

    public const RECURSAMIENTO_REPROBADO = 'RECURSAMIENTO_REPROBADO';

    public const SITUACIONES = [self::PENDIENTE => 'Pendiente', self::EN_RECURSAMIENTO => 'En recursamiento',
        self::REGULARIZADA => 'Regularizada', self::RECURSAMIENTO_REPROBADO => 'Recursamiento reprobado'];

    protected $table = 'trx_resultado_asignatura';

    // Todos los campos los ponen asentar() y los métodos de regularización.
    protected $fillable = [];

    protected function casts(): array
    {
        return ['aca_inscripcion_id' => 'integer', 'aca_grupo_asignatura_id' => 'integer', 'calificacion' => 'decimal:2', 'fecha_cierre' => 'date'];
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(AcaInscripcion::class, 'aca_inscripcion_id');
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(AcaGrupoAsignatura::class, 'aca_grupo_asignatura_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }

    // Recursamientos que parten de este resultado reprobado (a lo más uno; para volver a recursar se parte del resultado del recursamiento).
    public function recursamientos(): HasMany
    {
        return $this->hasMany(AcaRecursamiento::class, 'trx_resultado_asignatura_id');
    }

    public static function calificacionMinima(): string
    {
        return (string) (SysConfiguracion::actual()?->calificacion_minima ?? '6.00');
    }

    public static function calificacionMinimaExtraordinario(): string
    {
        $minima = config('siges.regularizacion.extraordinario_calificacion_minima');

        return $minima === null ? static::calificacionMinima() : TrxCalificacion::formatear(TrxCalificacion::centesimos($minima));
    }

    // Reglas vigentes (config/siges.php y Configuración del plantel), para mostrarlas antes del cierre.
    public static function reglas(): array
    {
        $asentada = config('siges.calificaciones.reprobatoria_asentada');

        return [
            'decimales' => TrxCalificacion::decimales(),
            'redondear' => (bool) config('siges.calificaciones.redondear_para_aprobar', true),
            'asentada' => $asentada === null ? null : number_format((float) $asentada, TrxCalificacion::decimales(), '.', ''),
            'minima' => static::calificacionMinima(),
        ];
    }

    /**
     * Definitiva = Σ(valor × peso) / 100, con los decimales y el redondeo (o truncado) de config/siges.php.
     * APROBADA si es ≥ calificación mínima; si reprueba y hay reprobatoria_asentada, se asienta ese valor.
     * $valores = [evaluación => valor]. Devuelve null si no hay evaluaciones o falta alguna calificación.
     * Resultado: ['ponderada' => '5.95', 'definitiva' => '6.0', 'estado' => 'APROBADA'].
     */
    public static function definitiva(Collection $evaluaciones, array $valores): ?array
    {
        if ($evaluaciones->isEmpty()) {
            return null;
        }
        // Centésimas × centésimas = millonésimas de punto una vez dividido entre 100 %.
        $suma = 0;
        foreach ($evaluaciones as $evaluacion) {
            $valor = $valores[$evaluacion->id] ?? null;
            if (blank($valor)) {
                return null;
            }
            $suma += TrxCalificacion::centesimos($valor) * TrxCalificacion::centesimos($evaluacion->peso_porcentaje);
        }
        $reglas = static::reglas();
        $divisor = 10 ** (6 - $reglas['decimales']);
        $escalada = $reglas['redondear'] ? intdiv($suma + intdiv($divisor, 2), $divisor) : intdiv($suma, $divisor);
        $aprobada = $escalada * 10 ** (2 - $reglas['decimales']) >= TrxCalificacion::centesimos($reglas['minima']);
        $definitiva = number_format($escalada / 10 ** $reglas['decimales'], $reglas['decimales'], '.', '');

        return [
            'ponderada' => rtrim(rtrim(number_format($suma / 1000000, 4, '.', ''), '0'), '.'),
            'definitiva' => ! $aprobada && $reglas['asentada'] !== null ? $reglas['asentada'] : $definitiva,
            'estado' => $aprobada ? self::APROBADA : self::REPROBADA,
        ];
    }

    /**
     * Situación de la materia antes de cerrar (no guarda nada): aprobados, reprobados, alumnos sin todas sus calificaciones,
     * resultados ya cerrados, qué impide cerrar y el cálculo de cada inscripción calificable (INSCRITO y recursantes activos).
     */
    public static function resumen(AcaGrupoAsignatura $materia): array
    {
        $evaluaciones = $materia->evaluaciones()->with('tipo')->get();
        $calificables = $materia->inscripcionesCalificables();
        $inscripciones = AcaInscripcion::with('alumno.persona')->whereIn('id', array_keys($calificables))->get();
        $valores = TrxCalificacion::whereIn('aca_evaluacion_id', $evaluaciones->pluck('id'))->whereIn('aca_inscripcion_id', $inscripciones->pluck('id'))
            ->get()->groupBy('aca_inscripcion_id')->map(fn (Collection $calificaciones) => $calificaciones->pluck('valor', 'aca_evaluacion_id')->all());
        $resultados = static::where('aca_grupo_asignatura_id', $materia->id)->whereIn('aca_inscripcion_id', $inscripciones->pluck('id'))->get()->keyBy('aca_inscripcion_id');

        $calculos = [];
        $faltantes = [];
        foreach ($inscripciones as $inscripcion) {
            $calculo = static::definitiva($evaluaciones, $valores->get($inscripcion->id, []));
            if ($calculo === null) {
                $faltantes[] = $inscripcion->alumno->persona->nombre_completo;
            } else {
                $calculos[$inscripcion->id] = $calculo + ['oportunidad' => $calificables[$inscripcion->id]];
            }
        }
        $pendientes = $materia->pendientesDeCierre($evaluaciones);
        if ($evaluaciones->isNotEmpty() && $faltantes) {
            $pendientes[] = count($faltantes) === 1 ? '1 alumno no tiene todas sus calificaciones.' : count($faltantes).' alumnos no tienen todas sus calificaciones.';
        }
        $estados = collect($calculos)->countBy('estado');
        $recursantes = count(array_filter($calificables, fn (string $oportunidad) => $oportunidad === self::RECURSAMIENTO));

        return [
            'aprobados' => $estados->get(self::APROBADA, 0),
            'reprobados' => $estados->get(self::REPROBADA, 0),
            'faltantes' => $faltantes,
            'inscritos' => count($calificables) - $recursantes,
            'recursantes' => $recursantes,
            'total' => count($calificables),
            'cerrados' => $resultados->count(),
            'pendientes' => $pendientes,
            'listo' => $pendientes === [] && $calificables !== [],
            'calculos' => $calculos,
            'resultados' => $resultados,
        ];
    }

    /**
     * Calcula y asienta el resultado de cada alumno calificable: ORDINARIO para los INSCRITO y RECURSAMIENTO para los
     * recursantes. Idempotente: crea la fila, la actualiza si cambió o la deja igual; no toca una fila con otra
     * oportunidad (p. ej. un extraordinario). Devuelve el conteo por caso.
     */
    public static function cerrarMateria(AcaGrupoAsignatura $materia, User $usuario): array
    {
        PeriodoDeTrabajo::exigir(PeriodoDeTrabajo::OPERAR, $materia->periodoEscolar());
        return DB::transaction(function () use ($materia, $usuario) {
            // Mientras se cierra, nadie captura ni cambia las evaluaciones.
            AcaEvaluacion::where('aca_grupo_asignatura_id', $materia->id)->lockForUpdate()->get();
            $resumen = static::resumen($materia);
            if (! $resumen['listo']) {
                $motivos = $resumen['total'] === 0 ? ['No tiene alumnos inscritos ni recursantes.'] : $resumen['pendientes'];
                throw ValidationException::withMessages(['accion' => "No se pueden cerrar los resultados de {$materia->nombre}: ".implode(' ', $motivos)]);
            }
            $conteo = ['creados' => 0, 'actualizados' => 0, 'sin_cambio' => 0, 'omitidos' => 0];
            foreach ($resumen['calculos'] as $inscripcion => $calculo) {
                $conteo[static::asentar($materia, $inscripcion, $calculo, $usuario, true)]++;
            }

            return $conteo;
        });
    }

    /**
     * Cierra las materias del grupo que están listas; las demás se informan sin detener el resto.
     * Devuelve ['cerradas' => [materia => conteo], 'pendientes' => [materia => motivo]].
     */
    public static function cerrarGrupo(AcaGrupoEscolar $grupo, User $usuario): array
    {
        PeriodoDeTrabajo::exigir(PeriodoDeTrabajo::OPERAR, $grupo->periodo()->first());
        return DB::transaction(function () use ($grupo, $usuario) {
            $cerradas = [];
            $pendientes = [];
            foreach ($grupo->materias()->with(['asignatura', 'grupo'])->ordenados()->get() as $materia) {
                $resumen = static::resumen($materia);
                if ($resumen['listo']) {
                    $cerradas[$materia->asignatura->nombre] = static::cerrarMateria($materia, $usuario);
                } else {
                    $pendientes[$materia->asignatura->nombre] = $resumen['total'] === 0 ? 'Sin alumnos inscritos.' : implode(' ', $resumen['pendientes']);
                }
            }

            return compact('cerradas', 'pendientes');
        });
    }

    // Tras corregir calificaciones: recalcula el resultado ya cerrado (ORDINARIO o RECURSAMIENTO) de esas inscripciones. No crea resultados.
    /**
     * Recalcula los resultados ya cerrados de $inscripciones (después de corregir calificaciones), con el motivo en curso.
     * Devuelve las inscripciones cuyo resultado vigente NO se tocó: el que ya tiene otra oportunidad (extraordinario)
     * y, con $soloOrdinario (correcciones de un periodo pasado), también los de RECURSAMIENTO y los ordinarios
     * que ya tienen recursamiento.
     */
    public static function recalcular(AcaGrupoAsignatura $materia, array $inscripciones, User $usuario, bool $soloOrdinario = false): array
    {
        $existentes = $inscripciones === [] ? collect() : static::with('recursamientos')->where('aca_grupo_asignatura_id', $materia->id)
            ->whereIn('aca_inscripcion_id', $inscripciones)->get()->keyBy('aca_inscripcion_id');
        if ($existentes->isEmpty()) {
            return [];
        }
        $calificables = $materia->inscripcionesCalificables();
        $evaluaciones = $materia->evaluaciones()->get();
        $valores = TrxCalificacion::whereIn('aca_evaluacion_id', $evaluaciones->pluck('id'))->whereIn('aca_inscripcion_id', $inscripciones)
            ->get()->groupBy('aca_inscripcion_id');
        $intactos = [];
        foreach ($existentes as $inscripcion => $resultado) {
            if ($soloOrdinario && ($resultado->oportunidad !== self::ORDINARIO || $resultado->recursamientos->isNotEmpty())) {
                $intactos[] = $inscripcion;

                continue;
            }
            $calculo = static::definitiva($evaluaciones, $valores->get($inscripcion, collect())->pluck('valor', 'aca_evaluacion_id')->all());
            if ($calculo !== null && isset($calificables[$inscripcion])
                && static::asentar($materia, $inscripcion, $calculo + ['oportunidad' => $calificables[$inscripcion]], $usuario, false) === 'omitidos') {
                $intactos[] = $inscripcion;
            }
        }

        return $intactos;
    }

    // Devuelve la clave del conteo: creados, actualizados, sin_cambio u omitidos.
    private static function asentar(AcaGrupoAsignatura $materia, int $inscripcion, array $calculo, User $usuario, bool $crear): string
    {
        $resultado = static::where('aca_inscripcion_id', $inscripcion)->where('aca_grupo_asignatura_id', $materia->id)->lockForUpdate()->first();
        // Un extraordinario ya sustituyó al ordinario: no se toca.
        if (($resultado && $resultado->oportunidad !== $calculo['oportunidad']) || (! $resultado && ! $crear)) {
            return 'omitidos';
        }
        $resultado ??= new static;
        $nuevo = ! $resultado->exists;
        $resultado->forceFill(['aca_inscripcion_id' => $inscripcion, 'aca_grupo_asignatura_id' => $materia->id, 'oportunidad' => $calculo['oportunidad'],
            'calificacion' => TrxCalificacion::formatear(TrxCalificacion::centesimos($calculo['definitiva'])), 'estado' => $calculo['estado']]);
        if (! $nuevo && ! $resultado->isDirty()) {
            return 'sin_cambio';
        }
        $resultado->forceFill(['fecha_cierre' => today(), 'registrado_por_user_id' => $usuario->id])->save();

        return $nuevo ? 'creados' : 'actualizados';
    }

    /*
    |--------------------------------------------------------------------------
    | Regularización: extraordinario y recursamiento
    |--------------------------------------------------------------------------
    */

    public function scopeReprobadas(Builder $consulta): Builder
    {
        return $consulta->where('trx_resultado_asignatura.estado', self::REPROBADA);
    }

    // Subconsulta: el recursamiento (aca_recursamiento) ya tiene resultado en su materia destino, opcionalmente con ese estado.
    private static function conResultadoDestino(?string $estado = null): \Closure
    {
        return fn (Consulta $destino) => $destino->select(DB::raw(1))->from('trx_resultado_asignatura as destino')
            ->whereColumn('destino.aca_inscripcion_id', 'trx_resultado_asignatura.aca_inscripcion_id')
            ->whereColumn('destino.aca_grupo_asignatura_id', 'aca_recursamiento.aca_grupo_asignatura_id')
            ->when($estado, fn (Consulta $consulta) => $consulta->where('destino.estado', $estado));
    }

    public function scopeDeSituacion(Builder $consulta, ?string $situacion): Builder
    {
        return match ($situacion) {
            self::PENDIENTE => $consulta->whereDoesntHave('recursamientos'),
            self::EN_RECURSAMIENTO => $consulta->whereHas('recursamientos', fn (Builder $recursamientos) => $recursamientos->whereNotExists(static::conResultadoDestino())),
            self::REGULARIZADA => $consulta->whereHas('recursamientos', fn (Builder $recursamientos) => $recursamientos->whereExists(static::conResultadoDestino(self::APROBADA))),
            self::RECURSAMIENTO_REPROBADO => $consulta->whereHas('recursamientos', fn (Builder $recursamientos) => $recursamientos->whereExists(static::conResultadoDestino(self::REPROBADA))),
            default => $consulta,
        };
    }

    // Materias que el alumno adeuda: REPROBADA sin recursamiento cerrado (pendientes o en recursamiento). Un recursamiento
    // reprobado no cuenta dos veces: su materia de origen ya está cerrada y la que adeuda es la del recursamiento.
    public function scopeSinRegularizar(Builder $consulta): Builder
    {
        return $consulta->reprobadas()->whereDoesntHave('recursamientos', fn (Builder $recursamientos) => $recursamientos->whereExists(static::conResultadoDestino()));
    }

    public function scopeDelPeriodo(Builder $consulta, ?int $periodo): Builder
    {
        return $periodo ? $consulta->whereHas('materia.grupo', fn (Builder $grupos) => $grupos->where('aca_periodo_escolar_id', $periodo)) : $consulta;
    }

    public function scopeDeGrupo(Builder $consulta, ?int $grupo): Builder
    {
        return $grupo ? $consulta->whereHas('materia', fn (Builder $materias) => $materias->where('aca_grupo_escolar_id', $grupo)) : $consulta;
    }

    public function scopeDeAsignatura(Builder $consulta, ?int $asignatura): Builder
    {
        return $asignatura ? $consulta->whereHas('materia', fn (Builder $materias) => $materias->where('aca_asignatura_id', $asignatura)) : $consulta;
    }

    public function scopeDeOportunidad(Builder $consulta, ?string $oportunidad): Builder
    {
        return $oportunidad ? $consulta->where('oportunidad', $oportunidad) : $consulta;
    }

    // [alumno => materias adeudadas], para el aviso del máximo de reprobadas.
    public static function adeudosPorAlumno(array $alumnos): Collection
    {
        return static::sinRegularizar()->join('aca_inscripcion as adeudo', 'adeudo.id', '=', 'trx_resultado_asignatura.aca_inscripcion_id')
            ->whereIn('adeudo.per_alumno_id', $alumnos)->groupBy('adeudo.per_alumno_id')
            ->selectRaw('adeudo.per_alumno_id, count(*) as total')->pluck('total', 'per_alumno_id')->map(fn ($total) => (int) $total);
    }

    public static function maximoReprobadas(): int
    {
        return (int) config('siges.regularizacion.maximo_reprobadas_para_reinscripcion', 3);
    }

    // Requiere los recursamientos cargados; usa AcaRecursamiento::cargarResultados() para precargar sus resultados.
    public function situacion(): ?string
    {
        if ($this->estado !== self::REPROBADA) {
            return null;
        }
        $recursamiento = $this->recursamientos->first();
        $destino = $recursamiento?->resultadoDestino();

        return match (true) {
            $recursamiento === null => self::PENDIENTE,
            $destino === null => self::EN_RECURSAMIENTO,
            $destino->estado === self::APROBADA => self::REGULARIZADA,
            default => self::RECURSAMIENTO_REPROBADO,
        };
    }

    // El periodo activo y los anteriores que todavía admiten extraordinario (config/siges.php).
    public static function periodosConExtraordinario(): array
    {
        $activo = AcaPeriodoEscolar::activo();
        if (! $activo) {
            return [];
        }
        $anteriores = max(0, (int) config('siges.regularizacion.extraordinario_periodos_posteriores', 1));

        return array_merge([$activo->id], AcaPeriodoEscolar::where('fecha_inicio', '<', $activo->fecha_inicio)->ordenados()
            ->limit($anteriores)->pluck('id')->all());
    }

    // Qué impide registrar el extraordinario (vacío = se puede). Requiere materia.grupo.
    public function problemasExtraordinario(): array
    {
        $problemas = [];
        if ($this->oportunidad !== self::ORDINARIO || $this->estado !== self::REPROBADA) {
            $problemas[] = 'Hay un solo extraordinario por materia y solo sobre un resultado ordinario reprobado.';
        }
        if (! in_array($this->materia->grupo->aca_periodo_escolar_id, static::periodosConExtraordinario(), true)) {
            $problemas[] = 'El periodo de la materia ya no admite extraordinario.';
        }
        if ($this->recursamientos()->exists()) {
            $problemas[] = 'La materia ya tiene un recursamiento asignado.';
        }

        return $problemas;
    }

    // Qué impide asignar un recursamiento (vacío = se puede). Requiere materia.grupo.periodo e inscripcion.alumno.estado.
    public function problemasRecursamiento(): array
    {
        $problemas = [];
        if ($this->estado !== self::REPROBADA) {
            $problemas[] = 'Solo se recursa una materia reprobada.';
        } elseif ($this->oportunidad === self::ORDINARIO && config('siges.regularizacion.recursamiento_requiere_extraordinario', true)) {
            $problemas[] = 'Primero se registra el extraordinario: solo recursa quien también lo reprueba.';
        }
        $activo = AcaPeriodoEscolar::activo();
        if (! $activo || $this->materia->grupo->periodo->fecha_inicio->gte($activo->fecha_inicio)) {
            $problemas[] = 'El recursamiento se asigna en un periodo posterior al de la reprobación.';
        }
        if (! $this->inscripcion->alumno->estaActivo()) {
            $problemas[] = 'El alumno no está activo.';
        }
        if ($this->recursamientos()->exists()) {
            $problemas[] = 'La materia ya tiene un recursamiento; si lo reprobó, se recursa desde el resultado de ese recursamiento.';
        }

        return $problemas;
    }

    // Calificación asentada y estado de un extraordinario, con su mínima y la reprobatoria asentada de config/siges.php.
    private static function calificarExtraordinario(string $calificacion): array
    {
        $centesimos = TrxCalificacion::centesimos($calificacion);
        $aprobada = $centesimos >= TrxCalificacion::centesimos(static::calificacionMinimaExtraordinario());
        $asentada = config('siges.calificaciones.reprobatoria_asentada');
        if (! $aprobada && $asentada !== null) {
            $centesimos = TrxCalificacion::centesimos($asentada);
        }

        return ['calificacion' => TrxCalificacion::formatear($centesimos), 'estado' => $aprobada ? self::APROBADA : self::REPROBADA];
    }

    /**
     * Registra el extraordinario: actualiza esta fila (oportunidad EXTRAORDINARIO, calificación, estado, fecha del examen).
     * La fecha va del cierre del ordinario a hoy. El motivo lo fija el controlador (Auditoria::motivo).
     */
    public function registrarExtraordinario(string $calificacion, CarbonInterface $fecha, User $usuario): void
    {
        DB::transaction(function () use ($calificacion, $fecha, $usuario) {
            static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $this->refresh()->load('materia.grupo');
            if ($problemas = $this->problemasExtraordinario()) {
                throw ValidationException::withMessages(['accion' => implode(' ', $problemas)]);
            }
            if ($fecha->lt($this->fecha_cierre) || $fecha->isAfter(today())) {
                throw ValidationException::withMessages(['fecha' => 'La fecha del extraordinario debe estar entre el cierre del ordinario ('.$this->fecha_cierre->format('d/m/Y').') y hoy.']);
            }
            $this->forceFill(static::calificarExtraordinario($calificacion) + ['oportunidad' => self::EXTRAORDINARIO,
                'fecha_cierre' => $fecha->toDateString(), 'registrado_por_user_id' => $usuario->id])->save();
        });
    }

    // Corrige la calificación o la fecha del mismo examen extraordinario (no es otro intento). Con motivo.
    public function corregirExtraordinario(string $calificacion, CarbonInterface $fecha, User $usuario): void
    {
        DB::transaction(function () use ($calificacion, $fecha, $usuario) {
            static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $this->refresh();
            if ($this->oportunidad !== self::EXTRAORDINARIO) {
                throw ValidationException::withMessages(['accion' => 'Solo se corrige un resultado de extraordinario.']);
            }
            if ($this->recursamientos()->exists()) {
                throw ValidationException::withMessages(['accion' => 'No se puede corregir: la materia ya tiene un recursamiento. Quítalo antes, si aún no tiene calificaciones.']);
            }
            if ($fecha->isAfter(today())) {
                throw ValidationException::withMessages(['fecha' => 'La fecha del extraordinario no puede ser futura.']);
            }
            $this->forceFill(static::calificarExtraordinario($calificacion) + ['fecha_cierre' => $fecha->toDateString(), 'registrado_por_user_id' => $usuario->id])->save();
        });
    }

    // Materias del periodo activo que imparten la misma asignatura, fuera del grupo actual del alumno.
    public function destinosRecursamiento(): Builder
    {
        $grupoActual = $this->inscripcion->alumno->inscripcionActual?->aca_grupo_escolar_id;

        return AcaGrupoAsignatura::where('aca_asignatura_id', $this->materia->aca_asignatura_id)
            ->whereHas('grupo', fn (Builder $grupos) => $grupos->delPeriodoActivo())
            ->when($grupoActual, fn (Builder $materias) => $materias->where('aca_grupo_escolar_id', '!=', $grupoActual));
    }

    // Asigna el recursamiento en $destino (misma asignatura, periodo activo, otro grupo) desde $fecha (dentro del periodo activo).
    public function asignarRecursamiento(AcaGrupoAsignatura $destino, CarbonInterface $fecha, User $usuario): AcaRecursamiento
    {
        return DB::transaction(function () use ($destino, $fecha, $usuario) {
            static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $this->refresh()->load(['materia.grupo.periodo', 'inscripcion.alumno.estado']);
            if ($problemas = $this->problemasRecursamiento()) {
                throw ValidationException::withMessages(['accion' => implode(' ', $problemas)]);
            }
            if (! $this->destinosRecursamiento()->whereKey($destino->getKey())->exists()) {
                throw ValidationException::withMessages(['aca_grupo_asignatura_id' => 'Elige un grupo del periodo activo que imparta la misma asignatura y no sea el grupo actual del alumno.']);
            }
            $periodo = AcaPeriodoEscolar::activo();
            if ($fecha->lt($periodo->fecha_inicio) || $fecha->gt($periodo->fecha_fin)) {
                throw ValidationException::withMessages(['fecha_inicio' => 'La fecha de inicio debe estar dentro del periodo '.$periodo->clave.'.']);
            }
            $recursamiento = new AcaRecursamiento;
            $recursamiento->forceFill(['trx_resultado_asignatura_id' => $this->id, 'aca_grupo_asignatura_id' => $destino->id,
                'fecha_inicio' => $fecha->toDateString(), 'registrado_por_user_id' => $usuario->id])->save();

            return $recursamiento;
        });
    }
}
