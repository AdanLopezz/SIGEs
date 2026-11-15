<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Auditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Aspirante (captación de Vinculación). Una persona (por CURP) por ciclo de ingreso (UNIQUE persona + ciclo).
 * Estados: PROSPECTO, REGISTRADO y DESCARTADO se cambian a mano con motivo; INSCRITO solo con convertir() (Control Escolar) y es final.
 * Si la CURP ya existe, se reutiliza la persona y se conservan sus datos (Control Escolar los revisa en el alta de alumno).
 */
class TrxAspirante extends Model
{
    use Auditable;

    public const PROSPECTO = 'PROSPECTO';

    public const REGISTRADO = 'REGISTRADO';

    public const INSCRITO = 'INSCRITO';

    public const DESCARTADO = 'DESCARTADO';

    public const ESTADOS = [self::PROSPECTO => 'Prospecto', self::REGISTRADO => 'Registrado', self::INSCRITO => 'Inscrito', self::DESCARTADO => 'Descartado'];

    // Estados que se eligen a mano (INSCRITO solo al convertir).
    public const ESTADOS_MANUALES = [self::PROSPECTO, self::REGISTRADO, self::DESCARTADO];

    public const CICLO_REGEX = '/^\d{4}-\d{4}$/';

    // Bandera de registrar(): la persona ya existía y se conservaron sus datos.
    public bool $personaReutilizada = false;

    protected $table = 'trx_aspirante';

    // per_persona_id y estado no son asignables en masa.
    protected $fillable = ['aca_carrera_id', 'cat_medio_difusion_id', 'escuela_procedencia', 'ciclo_ingreso'];

    protected function casts(): array
    {
        return ['aca_carrera_id' => 'integer', 'cat_medio_difusion_id' => 'integer'];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(PerPersona::class, 'per_persona_id');
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(AcaCarrera::class, 'aca_carrera_id');
    }

    public function medio(): BelongsTo
    {
        return $this->belongsTo(CatMedioDifusion::class, 'cat_medio_difusion_id');
    }

    // ── Ciclo ────────────────────────────────────────────────────────────────

    // AAAA-AAAA con el segundo año = primero + 1.
    public static function cicloValido(?string $ciclo): bool
    {
        return is_string($ciclo) && preg_match(self::CICLO_REGEX, $ciclo) && (int) substr($ciclo, 5, 4) === (int) substr($ciclo, 0, 4) + 1;
    }

    // El siguiente ciclo escolar (inicia en agosto): en noviembre de 2026 → 2027-2028; en marzo de 2027 → 2027-2028.
    public static function cicloSugerido(): string
    {
        $inicio = today()->month >= 8 ? today()->year + 1 : today()->year;

        return $inicio.'-'.($inicio + 1);
    }

    // ── Filtros ──────────────────────────────────────────────────────────────

    public function scopeDeCiclo(Builder $consulta, ?string $ciclo): Builder
    {
        return $ciclo ? $consulta->where('ciclo_ingreso', $ciclo) : $consulta;
    }

    public function scopeDeCarrera(Builder $consulta, ?int $carrera): Builder
    {
        return $carrera ? $consulta->where('aca_carrera_id', $carrera) : $consulta;
    }

    public function scopeDeMedio(Builder $consulta, ?int $medio): Builder
    {
        return $medio ? $consulta->where('cat_medio_difusion_id', $medio) : $consulta;
    }

    public function scopeDeEstado(Builder $consulta, ?string $estado): Builder
    {
        return $estado ? $consulta->where('trx_aspirante.estado', $estado) : $consulta;
    }

    public function scopeBuscar(Builder $consulta, ?string $texto): Builder
    {
        if (blank($texto)) {
            return $consulta;
        }
        $patron = '%'.addcslashes(trim($texto), '%_\\').'%';

        return $consulta->where(fn (Builder $aspirantes) => $aspirantes->where('escuela_procedencia', 'ilike', $patron)
            ->orWhereHas('persona', fn (Builder $personas) => $personas->where('curp', 'ilike', $patron)
                ->orWhereRaw("concat_ws(' ', nombre, primer_apellido, segundo_apellido) ilike ?", [$patron])));
    }

    public function scopeRecientes(Builder $consulta): Builder
    {
        return $consulta->orderByDesc('trx_aspirante.created_at')->orderByDesc('trx_aspirante.id');
    }

    public static function ciclos(): array
    {
        return static::query()->distinct()->orderByDesc('ciclo_ingreso')->pluck('ciclo_ingreso')->all();
    }

    public function getEstadoNombreAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function esEditable(): bool
    {
        return in_array($this->estado, [self::PROSPECTO, self::REGISTRADO], true);
    }

    /**
     * Persona que también es alumno, personal, tutor o tiene cuenta: sus datos no se editan desde Aspirantes.
     */
    public function personaCompartida(): bool
    {
        $persona = $this->persona;

        return $persona->alumno()->exists() || $persona->personal()->exists() || $persona->user()->exists()
            || PerTutorAlumno::where('per_persona_id', $persona->id)->exists();
    }

    // ── Operaciones ──────────────────────────────────────────────────────────

    public static function datosDe(array $datos): array
    {
        return Arr::only($datos, ['aca_carrera_id', 'cat_medio_difusion_id', 'escuela_procedencia', 'ciclo_ingreso']);
    }

    public static function registrar(array $datos, User $usuario): self
    {
        return DB::transaction(function () use ($datos) {
            abort_if($datos['curp'] === PerPersona::CURP_SISTEMA, 404);
            $persona = PerPersona::where('curp', $datos['curp'])->lockForUpdate()->first();
            if ($persona?->alumno()->exists()) {
                throw ValidationException::withMessages(['curp' => 'Esta persona ya está registrada como alumno.']);
            }
            $reutilizada = $persona !== null;
            $persona ??= PerPersona::create(PerPersona::datosDe($datos));
            static::exigirCicloLibre($persona->id, $datos['ciclo_ingreso']);
            Auditoria::motivoPorDefecto('Registro de aspirante');
            $aspirante = new static(static::datosDe($datos));
            $aspirante->forceFill(['per_persona_id' => $persona->id, 'estado' => self::PROSPECTO])->save();
            $aspirante->personaReutilizada = $reutilizada;

            return $aspirante;
        });
    }

    // Solo PROSPECTO o REGISTRADO. Los datos de la persona se actualizan solo si no es compartida. Motivo lo fija el controlador.
    public function actualizar(array $datos): void
    {
        DB::transaction(function () use ($datos) {
            $this->bloquear();
            if (! $this->esEditable()) {
                throw ValidationException::withMessages(['accion' => 'Solo se edita un aspirante prospecto o registrado.']);
            }
            if ($datos['ciclo_ingreso'] !== $this->ciclo_ingreso) {
                static::exigirCicloLibre($this->per_persona_id, $datos['ciclo_ingreso'], $this->id);
            }
            if (! $this->personaCompartida()) {
                $this->persona->update(PerPersona::datosDe(array_diff_key($datos, ['curp' => true])));
            }
            $this->fill(static::datosDe($datos))->save();
        });
    }

    // Entre PROSPECTO, REGISTRADO y DESCARTADO; nunca desde ni hacia INSCRITO. Motivo obligatorio (controlador).
    public function cambiarEstado(string $estado): void
    {
        DB::transaction(function () use ($estado) {
            $this->bloquear();
            if ($this->estado === self::INSCRITO) {
                throw ValidationException::withMessages(['accion' => 'El aspirante ya es alumno: su estado no cambia.']);
            }
            if (! in_array($estado, self::ESTADOS_MANUALES, true)) {
                throw ValidationException::withMessages(['accion' => 'Un aspirante pasa a Inscrito solo al convertirlo en alumno.']);
            }
            if ($estado === $this->estado) {
                throw ValidationException::withMessages(['accion' => 'El aspirante ya tiene ese estado.']);
            }
            $this->forceFill(['estado' => $estado])->save();
        });
    }

    /**
     * Convierte al aspirante REGISTRADO en alumno: el alta normal (PerAlumno::registrar, que reutiliza la persona por CURP) y el
     * aspirante pasa a INSCRITO en la misma transacción. Si algo falla, se revierte todo (también el archivo del expediente).
     */
    public function convertir(array $datos, UploadedFile $expediente, User $usuario): PerAlumno
    {
        return DB::transaction(function () use ($datos, $expediente, $usuario) {
            $this->bloquear();
            if ($this->estado !== self::REGISTRADO) {
                throw ValidationException::withMessages(['accion' => 'Solo se convierte en alumno un aspirante registrado.']);
            }
            if (($datos['curp'] ?? null) !== $this->persona->curp) {
                throw ValidationException::withMessages(['curp' => 'La CURP no corresponde al aspirante.']);
            }
            $alumno = PerAlumno::registrar($datos, $expediente, $usuario);
            Auditoria::conMotivo('Conversión de aspirante a alumno', fn () => $this->forceFill(['estado' => self::INSCRITO])->save());

            return $alumno;
        });
    }

    // Un aspirante por persona y ciclo (UNIQUE): mensaje claro antes de chocar con la BD.
    private static function exigirCicloLibre(int $persona, string $ciclo, ?int $excepto = null): void
    {
        $existe = static::where('per_persona_id', $persona)->where('ciclo_ingreso', $ciclo)->when($excepto, fn (Builder $c) => $c->whereKeyNot($excepto))->exists();
        if ($existe) {
            throw ValidationException::withMessages(['ciclo_ingreso' => "Esta persona ya está registrada como aspirante del ciclo {$ciclo}."]);
        }
    }

    private function bloquear(): void
    {
        static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
        $this->refresh();
    }
}
