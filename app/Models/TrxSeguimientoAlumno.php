<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Policies\SeguimientoPolicy;
use App\Support\Auditoria;
use App\Support\PeriodoDeTrabajo;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Caso de seguimiento de Orientación (seguimiento.administrar). Nunca se muestra en el portal.
 * Estados: ABIERTO → EN_SEGUIMIENTO (primera nota) → CERRADO (nota final) → EN_SEGUIMIENTO (reabrir). No se borra.
 * Abrir, notas, riesgo y reabrir: solo casos del periodo activo (OPERAR). Cerrar y "Continuar en el periodo actual": en cualquiera.
 */
#[UsePolicy(SeguimientoPolicy::class)]
class TrxSeguimientoAlumno extends Model
{
    use Auditable;

    public const ABIERTO = 'ABIERTO';

    public const EN_SEGUIMIENTO = 'EN_SEGUIMIENTO';

    public const CERRADO = 'CERRADO';

    public const ESTADOS = [self::ABIERTO => 'Abierto', self::EN_SEGUIMIENTO => 'En seguimiento', self::CERRADO => 'Cerrado'];

    public const MOTIVOS = [
        'REPROBACION' => 'Reprobación',
        'INASISTENCIAS' => 'Inasistencias',
        'CONDUCTA' => 'Conducta',
        'BAJO_RENDIMIENTO' => 'Bajo rendimiento',
        'EMOCIONAL' => 'Emocional',
        'FAMILIAR' => 'Familiar',
        'ECONOMICO' => 'Económico',
        'OTRO' => 'Otro',
    ];

    // Ven todos los casos; Orientador y Tutor, los de sus grupos (scopeVisiblesPara).
    public const ROLES_VEN_TODOS = ['Administrador', 'Subdirector'];

    protected $table = 'trx_seguimiento_alumno';

    // estado, inscripción, fecha y quién abrió no son asignables en masa.
    protected $fillable = ['motivo', 'descripcion', 'riesgo_abandono'];

    // La descripción puede ser emocional o familiar: en la bitácora solo queda que cambió.
    protected array $camposSensibles = ['descripcion'];

    protected string $etiquetaSensible = 'texto confidencial';

    protected function casts(): array
    {
        return ['fecha_apertura' => 'date', 'riesgo_abandono' => 'boolean'];
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(AcaInscripcion::class, 'aca_inscripcion_id');
    }

    public function abiertoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'abierto_por_user_id');
    }

    public function notas(): HasMany
    {
        return $this->hasMany(TrxSeguimientoNota::class, 'trx_seguimiento_alumno_id');
    }

    public function ultimaNota(): HasOne
    {
        return $this->hasOne(TrxSeguimientoNota::class, 'trx_seguimiento_alumno_id')->latestOfMany();
    }

    // ── Visibilidad ──────────────────────────────────────────────────────────

    /**
     * Administrador y Subdirector: todos. Orientador y Tutor:
     *  - los casos de una inscripción de un grupo que acompañan, del periodo activo o del de consulta;
     *  - todos los casos (de cualquier periodo) de los alumnos cuyo grupo vigente acompañan: los anteriores, en solo lectura.
     */
    public function scopeVisiblesPara(Builder $consulta, User $usuario): Builder
    {
        if ($usuario->hasAnyRole(self::ROLES_VEN_TODOS)) {
            return $consulta;
        }
        $periodos = array_values(array_unique(array_filter([PeriodoDeTrabajo::periodoActivo()?->id, PeriodoDeTrabajo::periodoConsulta()?->id])));
        $grupos = AcaGrupoEscolar::acompanadosPor($usuario, $periodos ?: [0])->select('aca_grupo_escolar.id');
        $actuales = AcaInscripcion::inscritas()->whereIn('aca_grupo_escolar_id', AcaGrupoEscolar::acompanadosPor($usuario)->select('aca_grupo_escolar.id'))
            ->select('per_alumno_id');

        return $consulta->whereHas('inscripcion', fn (Builder $inscripciones) => $inscripciones
            ->where(fn (Builder $inscripciones) => $inscripciones->whereIn('aca_grupo_escolar_id', $grupos)->orWhereIn('per_alumno_id', $actuales)));
    }

    public function esVisiblePara(User $usuario): bool
    {
        return static::whereKey($this->getKey())->visiblesPara($usuario)->exists();
    }

    // Alumnos con inscripción INSCRITO en el periodo activo, en un grupo que el usuario acompaña (todos para ROLES_VEN_TODOS).
    public static function alumnosElegibles(User $usuario): Builder
    {
        return PerAlumno::query()->whereHas('inscripciones', fn (Builder $inscripciones) => $inscripciones->inscritas()
            ->whereIn('aca_grupo_escolar_id', AcaGrupoEscolar::acompanadosPor($usuario)->select('aca_grupo_escolar.id')));
    }

    // ── Filtros ──────────────────────────────────────────────────────────────

    // Casos de una inscripción del periodo $periodo (el listado y el resumen usan el de consulta).
    public function scopeDelPeriodo(Builder $consulta, ?int $periodo): Builder
    {
        return $consulta->whereHas('inscripcion.grupo', fn (Builder $grupos) => $grupos->where('aca_periodo_escolar_id', $periodo ?? 0));
    }

    public function scopeDeEstado(Builder $consulta, ?string $estado): Builder
    {
        return $estado ? $consulta->where('trx_seguimiento_alumno.estado', $estado) : $consulta;
    }

    public function scopeDeMotivo(Builder $consulta, ?string $motivo): Builder
    {
        return $motivo ? $consulta->where('trx_seguimiento_alumno.motivo', $motivo) : $consulta;
    }

    public function scopeDeGrupo(Builder $consulta, ?int $grupo): Builder
    {
        return $grupo ? $consulta->whereHas('inscripcion', fn (Builder $inscripciones) => $inscripciones->where('aca_grupo_escolar_id', $grupo)) : $consulta;
    }

    public function scopeDeAlumno(Builder $consulta, ?int $alumno): Builder
    {
        return $alumno ? $consulta->whereHas('inscripcion', fn (Builder $inscripciones) => $inscripciones->where('per_alumno_id', $alumno)) : $consulta;
    }

    public function scopeEnRiesgo(Builder $consulta, bool $soloEnRiesgo = true): Builder
    {
        return $soloEnRiesgo ? $consulta->where('trx_seguimiento_alumno.riesgo_abandono', true) : $consulta;
    }

    // Abiertos = ABIERTO o EN_SEGUIMIENTO.
    public function scopeAbiertos(Builder $consulta): Builder
    {
        return $consulta->where('trx_seguimiento_alumno.estado', '<>', self::CERRADO);
    }

    public function scopeRecientes(Builder $consulta): Builder
    {
        return $consulta->orderByDesc('trx_seguimiento_alumno.fecha_apertura')->orderByDesc('trx_seguimiento_alumno.id');
    }

    // ── Estado ───────────────────────────────────────────────────────────────

    public function estaCerrado(): bool
    {
        return $this->estado === self::CERRADO;
    }

    // Notas, riesgo y reabrir: solo en casos del periodo activo.
    public function esDelPeriodoActivo(): bool
    {
        return PeriodoDeTrabajo::permite(PeriodoDeTrabajo::OPERAR, $this->periodoCargado());
    }

    // Caso abierto de un periodo pasado: se cierra o se continúa en el periodo actual.
    public function esDePeriodoPasado(): bool
    {
        return PeriodoDeTrabajo::tipo($this->periodoCargado()) === PeriodoDeTrabajo::PASADO;
    }

    public function getMotivoNombreAttribute(): string
    {
        return self::MOTIVOS[$this->motivo] ?? $this->motivo;
    }

    public function getEstadoNombreAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    // ── Operaciones ──────────────────────────────────────────────────────────

    /**
     * Abre un caso sobre la inscripción vigente del alumno. Si ya tiene un caso abierto con el mismo motivo
     * (de cualquier periodo), pide confirmación ($confirmado) antes de abrir otro.
     */
    public static function abrir(array $datos, User $usuario, bool $confirmado = false): self
    {
        return DB::transaction(function () use ($datos, $usuario, $confirmado) {
            $alumno = static::alumnosElegibles($usuario)->whereKey($datos['per_alumno_id'] ?? 0)->first();
            if (! $alumno) {
                throw ValidationException::withMessages(['per_alumno_id' => 'Elige un alumno de tus grupos con inscripción vigente.']);
            }
            $alumno->bloquear();
            $inscripcion = $alumno->inscripcionActual()->with('grupo.periodo')->first();
            if (! $inscripcion) {
                throw ValidationException::withMessages(['per_alumno_id' => 'El alumno no tiene inscripción vigente en el periodo activo.']);
            }
            PeriodoDeTrabajo::exigir(PeriodoDeTrabajo::OPERAR, $inscripcion->grupo->periodo);
            if (! $confirmado && static::abiertos()->deAlumno($alumno->id)->deMotivo($datos['motivo'])->exists()) {
                throw ValidationException::withMessages(['confirmar_duplicado' => 'El alumno ya tiene un caso abierto por '.mb_strtolower(self::MOTIVOS[$datos['motivo']] ?? $datos['motivo'])
                    .'. Revísalo o confirma que quieres abrir otro.']);
            }
            Auditoria::motivoPorDefecto('Apertura de seguimiento');

            return static::crearSobre($inscripcion, $datos, $usuario);
        });
    }

    // La primera nota pasa el caso de ABIERTO a EN_SEGUIMIENTO.
    public function agregarNota(array $datos, User $usuario): TrxSeguimientoNota
    {
        return DB::transaction(function () use ($datos, $usuario) {
            $this->bloquear();
            $this->exigirAbierto('Un caso cerrado no recibe notas; reábrelo primero.');
            $this->exigirPeriodoActivo();
            $fecha = Carbon::parse($datos['fecha'])->startOfDay();
            if ($fecha->isAfter(today())) {
                throw ValidationException::withMessages(['fecha' => 'La fecha de la nota no puede ser futura.']);
            }
            if ($fecha->lt($this->fecha_apertura)) {
                throw ValidationException::withMessages(['fecha' => 'La fecha de la nota no puede ser anterior a la apertura del caso ('.$this->fecha_apertura->format('d/m/Y').').']);
            }
            $nota = $this->crearNota($datos, $usuario);
            if ($this->estado === self::ABIERTO) {
                Auditoria::motivoPorDefecto('Primera nota del seguimiento');
                $this->forceFill(['estado' => self::EN_SEGUIMIENTO])->save();
            }

            return $nota;
        });
    }

    // Con la nota final (fecha de hoy). El motivo de la bitácora lo fija el controlador. Se permite en cualquier periodo.
    public function cerrar(array $nota, User $usuario): TrxSeguimientoNota
    {
        return DB::transaction(function () use ($nota, $usuario) {
            $this->bloquear();
            $this->exigirAbierto('El caso ya está cerrado.');
            $final = $this->crearNota(['fecha' => today()->toDateString()] + $nota, $usuario);
            $this->forceFill(['estado' => self::CERRADO])->save();

            return $final;
        });
    }

    // Un caso cerrado siempre tiene su nota final, así que vuelve a EN_SEGUIMIENTO.
    public function reabrir(): void
    {
        DB::transaction(function () {
            $this->bloquear();
            if (! $this->estaCerrado()) {
                throw ValidationException::withMessages(['accion' => 'Solo se reabre un caso cerrado.']);
            }
            $this->exigirPeriodoActivo();
            $this->forceFill(['estado' => self::EN_SEGUIMIENTO])->save();
        });
    }

    public function cambiarRiesgo(bool $riesgo): void
    {
        DB::transaction(function () use ($riesgo) {
            $this->bloquear();
            $this->exigirAbierto('El riesgo de un caso cerrado no cambia.');
            $this->exigirPeriodoActivo();
            if ($this->riesgo_abandono === $riesgo) {
                throw ValidationException::withMessages(['riesgo_abandono' => 'El riesgo de abandono ya tiene ese valor.']);
            }
            $this->forceFill(['riesgo_abandono' => $riesgo])->save();
        });
    }

    /**
     * Caso abierto de un periodo pasado: abre uno nuevo en el periodo activo (mismo motivo y riesgo, con la referencia
     * "Continúa del caso #N (periodo X)") y cierra este con la nota final, que remite al nuevo. Todo en una transacción.
     */
    public function continuarEnPeriodoActual(array $nota, User $usuario): self
    {
        return DB::transaction(function () use ($nota, $usuario) {
            $this->bloquear();
            $this->exigirAbierto('El caso ya está cerrado.');
            $anterior = $this->periodoCargado();
            if (PeriodoDeTrabajo::tipo($anterior) !== PeriodoDeTrabajo::PASADO) {
                throw ValidationException::withMessages(['accion' => 'Solo se continúa un caso de un periodo pasado.']);
            }
            $alumnoId = $this->inscripcion->per_alumno_id;
            $alumno = static::alumnosElegibles($usuario)->whereKey($alumnoId)->first();
            if (! $alumno) {
                throw ValidationException::withMessages(['accion' => 'El alumno no tiene inscripción vigente en uno de tus grupos del periodo activo.']);
            }
            $alumno->bloquear();
            $inscripcion = $alumno->inscripcionActual()->with('grupo.periodo')->first();
            PeriodoDeTrabajo::exigir(PeriodoDeTrabajo::OPERAR, $inscripcion?->grupo->periodo);
            $existente = static::abiertos()->deAlumno($alumnoId)->deMotivo($this->motivo)->delPeriodo($inscripcion->grupo->aca_periodo_escolar_id)->first();
            if ($existente) {
                throw ValidationException::withMessages(['accion' => 'El alumno ya tiene en el periodo activo un caso abierto por '.mb_strtolower($this->motivo_nombre)
                    .' (caso #'.$existente->id.'). Cierra este caso y continúa en aquel.']);
            }
            $nuevo = static::crearSobre($inscripcion, [
                'motivo' => $this->motivo,
                'riesgo_abandono' => $this->riesgo_abandono,
                'descripcion' => 'Continúa del caso #'.$this->id.' (periodo '.$anterior->nombre_visible.').'."\n\n".$this->descripcion,
            ], $usuario);
            $nota['descripcion'] = rtrim($nota['descripcion'])."\n\n".'Continúa en el caso #'.$nuevo->id.' (periodo '.$inscripcion->grupo->periodo->nombre_visible.').';
            $this->crearNota(['fecha' => today()->toDateString()] + $nota, $usuario);
            $this->forceFill(['estado' => self::CERRADO])->save();

            return $nuevo;
        });
    }

    // ── Resumen para la Subdirección ─────────────────────────────────────────

    /**
     * Conteos de los casos de $casos (ya filtrados por visibilidad y periodo): por estado, por motivo, en riesgo
     * y por grupo (con estado y riesgo).
     */
    public static function resumenDe(Builder $casos): array
    {
        $porEstado = (clone $casos)->reorder()->groupBy('trx_seguimiento_alumno.estado')
            ->selectRaw('trx_seguimiento_alumno.estado, count(*) as total')->pluck('total', 'estado')->map(fn ($total) => (int) $total);
        $porMotivo = (clone $casos)->reorder()->groupBy('trx_seguimiento_alumno.motivo')
            ->selectRaw('trx_seguimiento_alumno.motivo, count(*) as total')->pluck('total', 'motivo')->map(fn ($total) => (int) $total);
        $riesgoAbiertos = (clone $casos)->reorder()->enRiesgo()->abiertos()->count();
        $riesgo = (clone $casos)->reorder()->enRiesgo()->count();
        $porGrupo = (clone $casos)->reorder()->join('aca_inscripcion as caso_inscripcion', 'caso_inscripcion.id', '=', 'trx_seguimiento_alumno.aca_inscripcion_id')
            ->groupBy('caso_inscripcion.aca_grupo_escolar_id')
            ->selectRaw('caso_inscripcion.aca_grupo_escolar_id as grupo, count(*) as total')
            ->selectRaw("count(*) filter (where trx_seguimiento_alumno.estado = 'ABIERTO') as abiertos")
            ->selectRaw("count(*) filter (where trx_seguimiento_alumno.estado = 'EN_SEGUIMIENTO') as en_seguimiento")
            ->selectRaw("count(*) filter (where trx_seguimiento_alumno.estado = 'CERRADO') as cerrados")
            ->selectRaw('count(*) filter (where trx_seguimiento_alumno.riesgo_abandono) as en_riesgo')
            ->toBase()->get()->keyBy('grupo');

        return [
            'total' => $porEstado->sum(),
            'porEstado' => collect(self::ESTADOS)->map(fn ($nombre, $estado) => $porEstado->get($estado, 0)),
            'porMotivo' => collect(self::MOTIVOS)->map(fn ($nombre, $motivo) => $porMotivo->get($motivo, 0)),
            'enRiesgo' => $riesgo,
            'enRiesgoAbiertos' => $riesgoAbiertos,
            'porGrupo' => $porGrupo,
        ];
    }

    // ── Internos ─────────────────────────────────────────────────────────────

    private static function crearSobre(AcaInscripcion $inscripcion, array $datos, User $usuario): self
    {
        $caso = new static(['motivo' => $datos['motivo'], 'descripcion' => $datos['descripcion'], 'riesgo_abandono' => (bool) ($datos['riesgo_abandono'] ?? false)]);
        $caso->forceFill([
            'aca_inscripcion_id' => $inscripcion->id,
            'estado' => self::ABIERTO,
            'fecha_apertura' => today()->toDateString(),
            'abierto_por_user_id' => $usuario->id,
        ])->save();

        return $caso;
    }

    private function crearNota(array $datos, User $usuario): TrxSeguimientoNota
    {
        $nota = new TrxSeguimientoNota([
            'fecha' => $datos['fecha'],
            'tipo_atencion' => $datos['tipo_atencion'],
            'descripcion' => $datos['descripcion'],
            'acuerdos' => $datos['acuerdos'],
        ]);
        $nota->forceFill(['trx_seguimiento_alumno_id' => $this->id, 'registrado_por_user_id' => $usuario->id])->save();
        $this->unsetRelation('notas');
        $this->unsetRelation('ultimaNota');

        return $nota;
    }

    private function periodoCargado(): ?AcaPeriodoEscolar
    {
        return $this->loadMissing('inscripcion.grupo.periodo')->inscripcion?->grupo?->periodo;
    }

    private function exigirAbierto(string $mensaje): void
    {
        if ($this->estaCerrado()) {
            throw ValidationException::withMessages(['accion' => $mensaje]);
        }
    }

    private function exigirPeriodoActivo(): void
    {
        PeriodoDeTrabajo::exigir(PeriodoDeTrabajo::OPERAR, $this->periodoCargado());
    }

    private function bloquear(): void
    {
        static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
        $this->refresh();
    }
}
