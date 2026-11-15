<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Auditoria;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TrxPermisoSalida extends Model
{
    use Auditable;

    public const PENDIENTE = 'PENDIENTE';

    public const AUTORIZADO = 'AUTORIZADO';

    public const RECHAZADO = 'RECHAZADO';

    public const CANCELADO = 'CANCELADO';

    // estado => [etiqueta]. El color del badge lo define <x-badge-estado> (App\View\Components\BadgeEstado).
    public const ESTADOS = [
        self::PENDIENTE => ['Pendiente'],
        self::AUTORIZADO => ['Autorizado'],
        self::RECHAZADO => ['Rechazado'],
        self::CANCELADO => ['Cancelado'],
    ];

    protected $table = 'trx_permiso_salida';

    protected $fillable = ['aca_inscripcion_id', 'motivo', 'salida_prevista_at', 'estado', 'solicitado_por_user_id'];

    protected function casts(): array
    {
        return ['salida_prevista_at' => 'datetime'];
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(AcaInscripcion::class, 'aca_inscripcion_id');
    }

    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por_user_id');
    }

    public function confirmaciones(): HasMany
    {
        return $this->hasMany(TrxPermisoConfirmacion::class, 'trx_permiso_salida_id')->orderByDesc('confirmado_at');
    }

    public function scopeDeHoyEnAdelante(Builder $consulta): Builder
    {
        return $consulta->where('salida_prevista_at', '>=', today());
    }

    // Orientador y Tutor: solo de sus grupos; Prefectura y los demás roles de PerAlumno::ROLES_VEN_TODOS, todos.
    public function scopeVisiblesPara(Builder $consulta, User $usuario): Builder
    {
        return $consulta->whereHas('inscripcion.alumno', fn (Builder $alumnos) => $alumnos->visiblesPara($usuario));
    }

    public function esVisiblePara(User $usuario): bool
    {
        return static::whereKey($this->getKey())->visiblesPara($usuario)->exists();
    }

    public static function autorizadoHoyPara(AcaInscripcion $inscripcion): ?self
    {
        return static::where('aca_inscripcion_id', $inscripcion->id)->where('estado', self::AUTORIZADO)
            ->whereDate('salida_prevista_at', today())->orderBy('salida_prevista_at')->first();
    }

    public function puedeCancelarse(): bool
    {
        return in_array($this->estado, [self::PENDIENTE, self::AUTORIZADO], true) && $this->salida_prevista_at->isFuture();
    }

    // Desde el portal el padre solo confirma antes de la hora de salida (Prefectura puede registrar después).
    public function puedeConfirmarse(): bool
    {
        return $this->estado === self::PENDIENTE && $this->salida_prevista_at->isFuture();
    }

    public function scopeDeAlumno(Builder $consulta, int $alumno): Builder
    {
        return $consulta->whereHas('inscripcion', fn (Builder $inscripciones) => $inscripciones->where('per_alumno_id', $alumno));
    }

    /**
     * Confirmación del padre desde el portal: su per_tutor_alumno ACTIVO, método APP y como evidencia un folio con fecha y hora.
     * Reutiliza confirmar(); la confirmación y el cambio de estado quedan en la bitácora.
     */
    public function confirmarDesdePortal(User $usuario, string $decision): TrxPermisoConfirmacion
    {
        return DB::transaction(function () use ($usuario, $decision) {
            $permiso = static::with('inscripcion')->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            if (! $permiso->puedeConfirmarse()) {
                throw ValidationException::withMessages(['accion' => 'Solo se confirma un permiso pendiente antes de su hora de salida.']);
            }
            $tutor = PerTutorAlumno::activos()->where('per_alumno_id', $permiso->inscripcion->per_alumno_id)
                ->where('per_persona_id', $usuario->per_persona_id)->first()
                ?? throw ValidationException::withMessages(['accion' => 'No eres tutor activo del alumno de este permiso.']);
            $ahora = now();
            $evidencia = 'Folio APP-'.$ahora->format('Ymd-His').'-'.Str::upper(Str::random(4)).' · '.$ahora->format('d/m/Y H:i');
            Auditoria::motivoPorDefecto('Confirmación del tutor desde el portal');

            return $this->confirmar($tutor, $decision, 'APP', $evidencia, $usuario);
        });
    }

    public static function solicitar(PerAlumno $alumno, string $motivo, CarbonInterface $salida, User $usuario): self
    {
        return DB::transaction(function () use ($alumno, $motivo, $salida, $usuario) {
            $alumno->bloquear();
            $inscripcion = $alumno->inscripcionActual()->first();
            if (! $inscripcion) {
                throw ValidationException::withMessages(['per_alumno_id' => 'El alumno no tiene inscripción vigente en el periodo activo.']);
            }
            if ($salida->lt(now()->startOfMinute())) {
                throw ValidationException::withMessages(['salida_prevista_at' => 'La salida prevista no puede estar en el pasado.']);
            }

            return static::create([
                'aca_inscripcion_id' => $inscripcion->id,
                'motivo' => $motivo,
                'salida_prevista_at' => $salida,
                'estado' => self::PENDIENTE,
                'solicitado_por_user_id' => $usuario->id,
            ]);
        });
    }

    public function confirmar(PerTutorAlumno $tutor, string $decision, string $metodo, string $evidencia, User $usuario): TrxPermisoConfirmacion
    {
        return DB::transaction(function () use ($tutor, $decision, $metodo, $evidencia, $usuario) {
            $permiso = static::with('inscripcion')->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            if ($permiso->estado !== self::PENDIENTE) {
                throw ValidationException::withMessages(['accion' => 'Solo se confirma un permiso pendiente.']);
            }
            if ($tutor->per_alumno_id !== $permiso->inscripcion->per_alumno_id) {
                throw ValidationException::withMessages(['per_tutor_alumno_id' => 'El tutor no corresponde al alumno del permiso.']);
            }
            if (! $tutor->activo) {
                throw ValidationException::withMessages(['per_tutor_alumno_id' => 'El tutor está inactivo.']);
            }
            $confirmacion = $permiso->confirmaciones()->create([
                'per_tutor_alumno_id' => $tutor->id,
                'decision' => $decision,
                'metodo' => $metodo,
                'evidencia' => $evidencia,
                'registrado_por_user_id' => $usuario->id,
                'confirmado_at' => now(),
            ]);
            $permiso->update(['estado' => $decision === TrxPermisoConfirmacion::AUTORIZA ? self::AUTORIZADO : self::RECHAZADO]);
            $this->refresh();

            return $confirmacion;
        });
    }

    public function cancelar(): void
    {
        DB::transaction(function () {
            $permiso = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            if (! $permiso->puedeCancelarse()) {
                throw ValidationException::withMessages(['accion' => 'Solo se cancela un permiso pendiente o autorizado cuya hora de salida no ha pasado.']);
            }
            $permiso->update(['estado' => self::CANCELADO]);
            $this->refresh();
        });
    }
}
