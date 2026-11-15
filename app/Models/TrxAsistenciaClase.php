<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Auditoria;
use App\Support\PeriodoDeTrabajo;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pase de lista por clase: una fila por (inscripción, materia, fecha) (UNIQUE). Se audita completo, fila por fila.
 * - Lista: los INSCRITO del grupo (inscritos en esa fecha) más los recursantes activos de la materia, con su inscripción de origen.
 * - Fecha: periodo activo (OPERAR), día de clase (lunes a sábado, no inhábil, dentro del periodo activo), no futura y con
 *   clase de la materia ese día de la semana según el horario. Una sola lista por día aunque haya dos bloques.
 * - Quién: el docente de la materia (asistencia_clase.registrar) hasta `dias_para_corregir` días naturales después de la clase;
 *   después, Control Escolar (asistencia_clase.corregir) con motivo. Cambiar una lista guardada otro día también pide motivo.
 * - Incidencia que justifica falta: el día cubierto inicia en J y nunca queda en F (puede ser A o R si el alumno vino).
 *   Si la incidencia llega después, justificarPorIncidencia() pasa a J solo las F.
 * - Porcentaje = (A + R) / (clases − J) × 100; J no cuenta ni a favor ni en contra.
 */
class TrxAsistenciaClase extends Model
{
    use Auditable;

    public const DOCENTE = 'docente';

    public const CORRECCION = 'correccion';

    // pg_advisory_xact_lock(BLOQUEO, materia): un guardado de lista a la vez por materia.
    private const BLOQUEO = 17425;

    protected $table = 'trx_asistencia_clase';

    protected $fillable = ['aca_inscripcion_id', 'aca_grupo_asignatura_id', 'cat_tipo_asistencia_id', 'fecha'];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'aca_inscripcion_id' => 'integer', 'aca_grupo_asignatura_id' => 'integer', 'cat_tipo_asistencia_id' => 'integer'];
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(AcaInscripcion::class, 'aca_inscripcion_id');
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(AcaGrupoAsignatura::class, 'aca_grupo_asignatura_id');
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(CatTipoAsistencia::class, 'cat_tipo_asistencia_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }

    public static function diasParaCorregir(): int
    {
        return max(0, (int) config('siges.asistencia_clase.dias_para_corregir', 3));
    }

    public static function minimoPorcentaje(): float
    {
        return (float) config('siges.asistencia_clase.minimo_porcentaje', 80);
    }

    // Lunes a sábado, no inhábil y dentro del periodo activo (la asistencia diaria usa TrxAsistenciaDiaria::esDiaHabil, sin sábado).
    public static function esDiaDeClase(CarbonInterface $dia): bool
    {
        $periodo = PeriodoDeTrabajo::periodoActivo();
        $fecha = $dia->toDateString();

        return $dia->dayOfWeekIso <= 6
            && $periodo !== null
            && $fecha >= $periodo->fecha_inicio->toDateString()
            && $fecha <= $periodo->fecha_fin->toDateString()
            && AcaDiaInhabil::whereDate('fecha', $fecha)->doesntExist();
    }

    // El docente corrige su lista desde el día de la clase hasta `dias_para_corregir` días naturales después.
    public static function dentroDelPlazo(CarbonInterface $dia): bool
    {
        $fecha = $dia->toDateString();

        return $fecha <= today()->toDateString() && $fecha >= today()->subDays(static::diasParaCorregir())->toDateString();
    }

    // DOCENTE (su materia y dentro del plazo), CORRECCION (asistencia_clase.corregir, siempre con motivo) o null.
    public static function modoDeRegistro(User $usuario, AcaGrupoAsignatura $materia, CarbonInterface $dia): ?string
    {
        if ($usuario->can('asistencia_clase.registrar') && $materia->esDelDocente($usuario) && static::dentroDelPlazo($dia)) {
            return self::DOCENTE;
        }

        return $usuario->can('asistencia_clase.corregir') ? self::CORRECCION : null;
    }

    // Por qué no se puede pasar lista de la materia en ese día (null si se puede).
    public static function problemaDeFecha(AcaGrupoAsignatura $materia, CarbonInterface $dia): ?string
    {
        $materia->loadMissing('grupo.periodo');
        $fecha = $dia->format('d/m/Y');
        if (! PeriodoDeTrabajo::permite(PeriodoDeTrabajo::OPERAR, $materia->grupo->periodo)) {
            return 'Solo se pasa lista en el periodo activo; la de otros periodos se consulta.';
        }
        if ($dia->toDateString() > today()->toDateString()) {
            return "El {$fecha} todavía no llega: no se pasa lista de una fecha futura.";
        }
        if (! static::esDiaDeClase($dia)) {
            return "El {$fecha} no es día de clases (domingo, día inhábil o fuera del periodo activo).";
        }
        if (! in_array($dia->dayOfWeekIso, $materia->diasDeClase(), true)) {
            return 'Según el horario, '.$materia->asignatura()->value('nombre').' no tiene clase los '.mb_strtolower(AcaHorario::DIAS[$dia->dayOfWeekIso]).'.';
        }

        return null;
    }

    // Alumnos (per_alumno_id) con una incidencia que justifica falta y cubre la fecha; cuenta aunque esté cerrada (como cerrarDia()).
    public static function alumnosCubiertos(array $alumnos, string $fecha): array
    {
        if ($alumnos === []) {
            return [];
        }

        return TrxIncidencia::whereIn('per_alumno_id', $alumnos)
            ->whereHas('tipo', fn (Builder $tipos) => $tipos->where('justifica_falta', true))
            ->whereDate('fecha_inicio', '<=', $fecha)
            ->whereRaw('coalesce(fecha_fin, fecha_inicio) >= ?::date', [$fecha])
            ->distinct()->pluck('per_alumno_id')->map(fn ($alumno) => (int) $alumno)->all();
    }

    /**
     * Lista de la materia en $dia. Cada fila: inscripcion, oportunidad (ORDINARIO | RECURSAMIENTO), registro (o null),
     * codigo (lo guardado, o J si está cubierto, o A), cubierta (incidencia que justifica) y editable.
     * Primero los INSCRITO, luego los recursantes y al final, en solo lectura, quienes ya tenían registro y dejaron la lista.
     */
    public static function lista(AcaGrupoAsignatura $materia, CarbonInterface $dia): Collection
    {
        $materia->loadMissing('grupo');
        $fecha = $dia->toDateString();
        $inscritas = $materia->grupo->inscripciones()->inscritas()->whereDate('fecha_inscripcion', '<=', $fecha)->with('alumno.persona')->get();
        $recursantes = $materia->recursamientos()->whereDate('fecha_inicio', '<=', $fecha)
            ->with(['origen.inscripcion.alumno.persona', 'origen.inscripcion.alumno.estado'])->get()
            ->filter(fn (AcaRecursamiento $recursamiento) => $recursamiento->origen->inscripcion->alumno->estaActivo())
            ->map(fn (AcaRecursamiento $recursamiento) => $recursamiento->origen->inscripcion);
        $registros = static::with('tipo')->where('aca_grupo_asignatura_id', $materia->id)->whereDate('fecha', $fecha)->get()->keyBy('aca_inscripcion_id');
        $enLista = $inscritas->pluck('id')->merge($recursantes->pluck('id'));
        $otras = AcaInscripcion::with('alumno.persona')->whereIn('id', $registros->keys()->diff($enLista)->all())->get();
        $cubiertos = static::alumnosCubiertos($inscritas->merge($recursantes)->merge($otras)->pluck('per_alumno_id')->unique()->values()->all(), $fecha);
        $tipos = CatTipoAsistencia::porCodigo()->pluck('codigo', 'id');
        $orden = fn (AcaInscripcion $inscripcion) => mb_strtolower(implode(' ', [$inscripcion->alumno->persona->primer_apellido,
            $inscripcion->alumno->persona->segundo_apellido, $inscripcion->alumno->persona->nombre]));
        $fila = function (AcaInscripcion $inscripcion, string $oportunidad, bool $editable) use ($registros, $cubiertos, $tipos) {
            $registro = $registros->get($inscripcion->id);
            $cubierta = in_array((int) $inscripcion->per_alumno_id, $cubiertos, true);

            return (object) [
                'inscripcion' => $inscripcion,
                'alumno' => $inscripcion->alumno,
                'oportunidad' => $oportunidad,
                'registro' => $registro,
                'codigo' => $registro ? $tipos[$registro->cat_tipo_asistencia_id] : ($cubierta ? CatTipoAsistencia::JUSTIFICADA : CatTipoAsistencia::ASISTENCIA),
                'cubierta' => $cubierta,
                'editable' => $editable,
            ];
        };

        return $inscritas->sortBy($orden)->map(fn ($inscripcion) => $fila($inscripcion, TrxResultadoAsignatura::ORDINARIO, true))
            ->merge($recursantes->sortBy($orden)->map(fn ($inscripcion) => $fila($inscripcion, TrxResultadoAsignatura::RECURSAMIENTO, true)))
            ->merge($otras->sortBy($orden)->map(fn ($inscripcion) => $fila($inscripcion, TrxResultadoAsignatura::ORDINARIO, false)))
            ->values();
    }

    /**
     * Guarda la lista de la materia en $dia en una transacción, fila por fila (crea o actualiza; cada cambio queda en la bitácora).
     * $marcas = [aca_inscripcion_id => A|F|R|J]; quien no viene en $marcas queda en A (o J si está cubierto).
     * Devuelve cuántas filas creó o cambió.
     */
    public static function registrarLista(AcaGrupoAsignatura $materia, CarbonInterface $dia, array $marcas, User $usuario, ?string $motivo = null): int
    {
        $materia->loadMissing('grupo.periodo');
        PeriodoDeTrabajo::exigir(PeriodoDeTrabajo::OPERAR, $materia->grupo->periodo);
        if ($problema = static::problemaDeFecha($materia, $dia)) {
            throw ValidationException::withMessages(['fecha' => $problema]);
        }
        $modo = static::modoDeRegistro($usuario, $materia, $dia) ?? throw ValidationException::withMessages(['accion' => 'No puedes pasar lista de esta materia en esa fecha: el docente tiene '
            .static::diasParaCorregir().' días naturales después de la clase; después la corrige Control Escolar.']);
        $motivo = trim((string) $motivo) ?: null;

        return DB::transaction(function () use ($materia, $dia, $marcas, $usuario, $modo, $motivo) {
            DB::select('select pg_advisory_xact_lock(?, ?)', [self::BLOQUEO, $materia->id]);
            $fecha = $dia->toDateString();
            $existentes = static::where('aca_grupo_asignatura_id', $materia->id)->whereDate('fecha', $fecha)->lockForUpdate()->get()->keyBy('aca_inscripcion_id');
            $cambios = [];
            $errores = [];
            foreach (static::lista($materia, $dia)->where('editable', true) as $fila) {
                $id = $fila->inscripcion->id;
                $nombre = $fila->alumno->persona->nombre_completo;
                $codigo = $marcas[$id] ?? ($fila->cubierta ? CatTipoAsistencia::JUSTIFICADA : CatTipoAsistencia::ASISTENCIA);
                if (! in_array($codigo, CatTipoAsistencia::codigos(), true)) {
                    $errores["marcas.{$id}"] = "Elige A, F, R o J para {$nombre}.";

                    continue;
                }
                if ($fila->cubierta && $codigo === CatTipoAsistencia::FALTA) {
                    $errores["marcas.{$id}"] = "{$nombre} tiene una incidencia que justifica la falta ese día: no puede quedar como falta.";

                    continue;
                }
                $tipo = CatTipoAsistencia::idDe($codigo);
                $registro = $existentes->get($id);
                if (! $registro || $registro->cat_tipo_asistencia_id !== $tipo) {
                    $cambios[] = [$registro, $id, $tipo];
                }
            }
            if ($errores) {
                throw ValidationException::withMessages($errores);
            }
            $modificaGuardada = collect($cambios)->contains(fn (array $cambio) => $cambio[0] !== null);
            if (! $motivo && ($modo === self::CORRECCION && $cambios || $modificaGuardada && $fecha !== today()->toDateString())) {
                throw ValidationException::withMessages(['motivo' => $modo === self::CORRECCION
                    ? 'Escribe el motivo de la corrección.'
                    : 'Escribe el motivo: estás cambiando una lista ya guardada de otro día.']);
            }

            Auditoria::conMotivo($motivo ?? 'Pase de lista', function () use ($cambios, $materia, $fecha, $usuario) {
                foreach ($cambios as [$registro, $inscripcion, $tipo]) {
                    ($registro ?? new static)->forceFill([
                        'aca_inscripcion_id' => $inscripcion,
                        'aca_grupo_asignatura_id' => $materia->id,
                        'fecha' => $fecha,
                        'cat_tipo_asistencia_id' => $tipo,
                        'registrado_por_user_id' => $usuario->id,
                    ])->save();
                }
            });

            return count($cambios);
        });
    }

    /**
     * Incidencia que justifica falta: pasa a J las F de clase del alumno en los días que cubre (las R y A se respetan).
     * Fila por fila, con el motivo de la incidencia, como TrxAsistenciaDiaria::justificarPorIncidencia().
     */
    public static function justificarPorIncidencia(TrxIncidencia $incidencia): int
    {
        $filas = static::where('cat_tipo_asistencia_id', CatTipoAsistencia::idDe(CatTipoAsistencia::FALTA))
            ->whereBetween('fecha', [$incidencia->fecha_inicio->toDateString(), ($incidencia->fecha_fin ?? $incidencia->fecha_inicio)->toDateString()])
            ->whereHas('inscripcion', fn (Builder $inscripciones) => $inscripciones->where('per_alumno_id', $incidencia->per_alumno_id))
            ->lockForUpdate()->get();
        $justificada = CatTipoAsistencia::idDe(CatTipoAsistencia::JUSTIFICADA);
        Auditoria::conMotivo("Justificada por la incidencia #{$incidencia->id} ({$incidencia->tipo->nombre})", function () use ($filas, $justificada) {
            $filas->each(fn (self $fila) => $fila->forceFill(['cat_tipo_asistencia_id' => $justificada])->save());
        });

        return $filas->count();
    }

    // Materias (ids) de la lista que ya tienen lista guardada en la fecha.
    public static function materiasRegistradas(array $materias, string $fecha): array
    {
        return static::whereIn('aca_grupo_asignatura_id', $materias)->whereDate('fecha', $fecha)
            ->distinct()->pluck('aca_grupo_asignatura_id')->map(fn ($materia) => (int) $materia)->all();
    }

    /**
     * Totales por (inscripción, materia): clases, asistencias (A y R), faltas, retardos y justificadas.
     * El porcentaje sale del accesor (porcentaje) y deja fuera las J.
     */
    public function scopeResumen(Builder $consulta): Builder
    {
        return $consulta->join('cat_tipo_asistencia', 'cat_tipo_asistencia.id', '=', 'trx_asistencia_clase.cat_tipo_asistencia_id')
            ->select(['trx_asistencia_clase.aca_inscripcion_id', 'trx_asistencia_clase.aca_grupo_asignatura_id'])
            ->selectRaw('count(*) as clases')
            ->selectRaw("count(*) filter (where cat_tipo_asistencia.codigo <> 'J' and cat_tipo_asistencia.cuenta_como_asistencia) as asistencias")
            ->selectRaw("count(*) filter (where cat_tipo_asistencia.codigo = 'F') as faltas")
            ->selectRaw("count(*) filter (where cat_tipo_asistencia.codigo = 'R') as retardos")
            ->selectRaw("count(*) filter (where cat_tipo_asistencia.codigo = 'J') as justificadas")
            ->groupBy('trx_asistencia_clase.aca_inscripcion_id', 'trx_asistencia_clase.aca_grupo_asignatura_id');
    }

    // Sobre scopeResumen(): solo las filas con porcentaje bajo el mínimo.
    public function scopeBajoMinimo(Builder $consulta): Builder
    {
        return $consulta->havingRaw("count(*) filter (where cat_tipo_asistencia.codigo <> 'J') > 0")
            ->havingRaw("100.0 * count(*) filter (where cat_tipo_asistencia.codigo <> 'J' and cat_tipo_asistencia.cuenta_como_asistencia)
                / count(*) filter (where cat_tipo_asistencia.codigo <> 'J') < ?", [static::minimoPorcentaje()]);
    }

    // Solo en filas de scopeResumen().
    public function getPorcentajeAttribute(): ?float
    {
        return static::porcentaje((int) $this->asistencias, (int) $this->clases - (int) $this->justificadas);
    }

    public function getBajoMinimoAttribute(): bool
    {
        return static::esBajoMinimo($this->porcentaje);
    }

    public static function porcentaje(int $asistencias, int $consideradas): ?float
    {
        return $consideradas > 0 ? round(100 * $asistencias / $consideradas, 1) : null;
    }

    public static function esBajoMinimo(?float $porcentaje): bool
    {
        return $porcentaje !== null && $porcentaje < static::minimoPorcentaje();
    }

    public static function formatear(?float $porcentaje): string
    {
        return $porcentaje === null ? '—' : rtrim(rtrim(number_format($porcentaje, 1, '.', ''), '0'), '.').' %';
    }

    /**
     * Asistencia por materia del alumno en el periodo (ficha y portal): las materias de su grupo del periodo y las de sus
     * recursamientos, cada una con clases, faltas, retardos, justificadas, porcentaje y bajo_minimo (clases = 0: sin registros).
     */
    public static function resumenDelAlumno(PerAlumno $alumno, int $periodo): Collection
    {
        $inscripciones = $alumno->inscripciones()->pluck('id');
        $delPeriodo = $alumno->inscripciones()->delPeriodoConsulta($periodo)->with('grupo')->orderByRaw("estado = 'INSCRITO' desc")->orderByDesc('id')->first();
        $materias = $delPeriodo ? $delPeriodo->grupo->materias()->with(['asignatura', 'docente.persona'])->get() : collect();
        $recursadas = AcaRecursamiento::with(['materia.asignatura', 'materia.docente.persona', 'materia.grupo'])->delAlumno($alumno->id)
            ->whereHas('materia.grupo', fn (Builder $grupos) => $grupos->where('aca_periodo_escolar_id', $periodo))->get()->pluck('materia');
        $totales = static::resumen()->whereIn('trx_asistencia_clase.aca_inscripcion_id', $inscripciones)
            ->whereIn('trx_asistencia_clase.aca_grupo_asignatura_id', $materias->pluck('id')->merge($recursadas->pluck('id')))
            ->get()->groupBy('aca_grupo_asignatura_id');
        $fila = function (AcaGrupoAsignatura $materia, bool $recursamiento) use ($totales) {
            $deLaMateria = $totales->get($materia->id, collect());
            $suma = fn (string $campo) => (int) $deLaMateria->sum($campo);
            $porcentaje = static::porcentaje($suma('asistencias'), $suma('clases') - $suma('justificadas'));

            return (object) ['materia' => $materia, 'recursamiento' => $recursamiento, 'clases' => $suma('clases'), 'faltas' => $suma('faltas'),
                'retardos' => $suma('retardos'), 'justificadas' => $suma('justificadas'), 'porcentaje' => $porcentaje, 'bajo_minimo' => static::esBajoMinimo($porcentaje)];
        };

        return $materias->sortBy('asignatura.nombre')->map(fn ($materia) => $fila($materia, false))
            ->merge($recursadas->sortBy('asignatura.nombre')->map(fn ($materia) => $fila($materia, true)))->values();
    }
}
