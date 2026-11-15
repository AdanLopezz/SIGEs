<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\EliminaSiNoEstaEnUso;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PerTutorAlumno extends Model
{
    use Auditable;

    use EliminaSiNoEstaEnUso { eliminar as private eliminarSinReferencias; }

    public const PARENTESCOS = ['MADRE' => 'Madre', 'PADRE' => 'Padre', 'TUTOR_LEGAL' => 'Tutor legal', 'ABUELO' => 'Abuelo(a)', 'HERMANO' => 'Hermano(a)', 'OTRO' => 'Otro'];

    public bool $personaReutilizada = false;

    protected $table = 'per_tutor_alumno';

    // "activo" solo cambia con desactivar() y reactivar().
    protected $fillable = ['per_persona_id', 'parentesco', 'es_contacto_emergencia', 'recibe_notificaciones'];

    protected $attributes = ['activo' => true];

    protected function casts(): array
    {
        return ['es_contacto_emergencia' => 'boolean', 'recibe_notificaciones' => 'boolean', 'activo' => 'boolean'];
    }

    // Un tutor inactivo conserva su historial, pero no cuenta como tutor: no recibe avisos ni ve al alumno en el portal.
    public function scopeActivos(Builder $consulta): Builder
    {
        return $consulta->where('per_tutor_alumno.activo', true);
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(PerAlumno::class, 'per_alumno_id');
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(PerPersona::class, 'per_persona_id');
    }

    public function confirmaciones(): HasMany
    {
        return $this->hasMany(TrxPermisoConfirmacion::class, 'per_tutor_alumno_id');
    }

    // Con consentimientos o confirmaciones de permisos ya no se borra: se desactiva.
    public function tieneHistorial(): bool
    {
        return $this->consentimientos()->exists() || $this->confirmaciones()->exists();
    }

    public function consentimientos(): HasMany
    {
        return $this->hasMany(PerConsentimiento::class, 'per_tutor_alumno_id');
    }

    public function consentimientoVigente(string $tipo): ?PerConsentimiento
    {
        return $this->consentimientos()->where('tipo', $tipo)->recientes()->first();
    }

    // Otorgar o revocar = fila nueva con su documento firmado; el anterior queda como historial.
    public function registrarConsentimiento(string $tipo, bool $otorgado, CarbonInterface $fecha, UploadedFile $firmado, User $usuario): PerConsentimiento
    {
        return DB::transaction(function () use ($tipo, $otorgado, $fecha, $firmado, $usuario) {
            $this->alumno->bloquear();
            $this->refresh();
            $vigente = $this->consentimientoVigente($tipo);
            if ($vigente && $fecha->lt($vigente->fecha)) {
                throw ValidationException::withMessages(['fecha' => 'La fecha no puede ser anterior a la del consentimiento vigente ('.$vigente->fecha->format('d/m/Y').').']);
            }
            $archivo = Archivo::guardar($firmado, 'consentimientos', $usuario);
            $consentimiento = $this->consentimientos()->create([
                'tipo' => $tipo,
                'otorgado' => $otorgado,
                'fecha' => $fecha->toDateString(),
                'archivo_id' => $archivo->id,
            ]);
            // El consentimiento de notificaciones manda sobre la casilla del tutor.
            if ($tipo === PerConsentimiento::NOTIFICACIONES && $this->recibe_notificaciones !== $otorgado) {
                $this->forceFill(['recibe_notificaciones' => $otorgado])->save();
            }

            return $consentimiento;
        });
    }

    public function getParentescoNombreAttribute(): string
    {
        return self::PARENTESCOS[$this->parentesco] ?? $this->parentesco;
    }

    // Si la persona ya existe por CURP se reutiliza sin modificar sus datos.
    public static function registrar(PerAlumno $alumno, array $datos): self
    {
        return DB::transaction(function () use ($alumno, $datos) {
            $alumno->bloquear();
            abort_if($datos['curp'] === PerPersona::CURP_SISTEMA, 404);
            $persona = PerPersona::where('curp', $datos['curp'])->lockForUpdate()->first();
            $reutilizada = $persona !== null;
            if (! $persona) {
                if (! empty($datos['mismo_domicilio'])) {
                    $datos = $alumno->persona->copiarDomicilioDe($datos);
                }
                $persona = PerPersona::create(PerPersona::datosDe($datos));
            }
            if ($persona->id === $alumno->per_persona_id) {
                throw ValidationException::withMessages(['curp' => 'El tutor no puede ser el mismo alumno.']);
            }
            $existente = $alumno->tutores()->where('per_persona_id', $persona->id)->first();
            if ($existente) {
                throw ValidationException::withMessages(['curp' => $existente->activo
                    ? 'Esta persona ya está registrada como tutor del alumno.'
                    : 'Esta persona ya es tutor del alumno, pero está inactivo: reactívalo en la lista.']);
            }
            $tutor = $alumno->tutores()->create([
                'per_persona_id' => $persona->id,
                'parentesco' => $datos['parentesco'],
                'es_contacto_emergencia' => (bool) ($datos['es_contacto_emergencia'] ?? false),
                'recibe_notificaciones' => (bool) ($datos['recibe_notificaciones'] ?? false),
            ]);
            $tutor->personaReutilizada = $reutilizada;

            return $tutor;
        });
    }

    public function actualizar(array $datos): void
    {
        DB::transaction(function () use ($datos) {
            $this->alumno->bloquear();
            $this->refresh();
            if ($this->activo && $this->es_contacto_emergencia && empty($datos['es_contacto_emergencia']) && $this->esUltimoContactoEmergencia()) {
                throw ValidationException::withMessages(['es_contacto_emergencia' => 'Debe quedar al menos un contacto de emergencia.']);
            }
            // Con un consentimiento de notificaciones vigente, la casilla sigue a ese consentimiento.
            $notificaciones = $this->consentimientoVigente(PerConsentimiento::NOTIFICACIONES);
            $this->update([
                'parentesco' => $datos['parentesco'],
                'es_contacto_emergencia' => (bool) ($datos['es_contacto_emergencia'] ?? false),
                'recibe_notificaciones' => $notificaciones ? $notificaciones->otorgado : (bool) ($datos['recibe_notificaciones'] ?? false),
            ]);
        });
    }

    // Borra al tutor solo si no tiene historial (si lo tiene, devuelve false: se desactiva).
    public function quitar(): bool
    {
        return DB::transaction(function () {
            $this->alumno->bloquear();
            $this->refresh();
            $this->exigirQueNoSeaElUltimo('quitar');

            return $this->eliminarSinReferencias();
        });
    }

    // El motivo lo fija el controlador (Auditoria::motivo).
    public function desactivar(): void
    {
        DB::transaction(function () {
            $this->alumno->bloquear();
            $this->refresh();
            if (! $this->activo) {
                throw ValidationException::withMessages(['accion' => 'El tutor ya está inactivo.']);
            }
            $this->exigirQueNoSeaElUltimo('desactivar');
            $this->forceFill(['activo' => false])->save();
        });
    }

    public function reactivar(): void
    {
        DB::transaction(function () {
            $this->alumno->bloquear();
            $this->refresh();
            if ($this->activo) {
                throw ValidationException::withMessages(['accion' => 'El tutor ya está activo.']);
            }
            $this->forceFill(['activo' => true])->save();
        });
    }

    // Los tutores inactivos no cuentan: debe quedar al menos un tutor activo y un contacto de emergencia activo.
    private function exigirQueNoSeaElUltimo(string $accion): void
    {
        if (! $this->activo) {
            return;
        }
        if ($this->alumno->tutores()->activos()->whereKeyNot($this->id)->doesntExist()) {
            throw ValidationException::withMessages(['accion' => "No se puede {$accion} al único tutor activo del alumno."]);
        }
        if ($this->es_contacto_emergencia && $this->esUltimoContactoEmergencia()) {
            throw ValidationException::withMessages(['accion' => "No se puede {$accion} al último contacto de emergencia activo. Marca primero a otro tutor."]);
        }
    }

    // $usuario: el generado para el tutor (CredencialesDeAcceso); si la persona ya tiene cuenta, solo se le agrega el rol.
    public function crearCuenta(string $usuario): User
    {
        if (! $this->activo) {
            throw ValidationException::withMessages(['accion' => 'El tutor está inactivo: reactívalo antes de darle acceso.']);
        }

        return User::darAccesoControlEscolar($this->persona, $usuario, 'Padre de familia');
    }

    private function esUltimoContactoEmergencia(): bool
    {
        return $this->alumno->tutores()->activos()->whereKeyNot($this->id)->where('es_contacto_emergencia', true)->doesntExist();
    }
}
