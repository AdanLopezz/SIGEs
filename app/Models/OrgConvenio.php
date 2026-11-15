<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Auditoria;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Convenio con una empresa. Documento firmado obligatorio (Archivo, carpeta "convenios"). No se borra.
 * Estado guardado: VIGENTE → VENCIDO (comando siges:vencer-convenios, diario) o CANCELADO (con motivo).
 * Estado efectivo: un VIGENTE con fecha_fin anterior a hoy ya se muestra y se filtra como VENCIDO aunque el comando no haya corrido.
 */
class OrgConvenio extends Model
{
    use Auditable;

    public const VIGENTE = 'VIGENTE';

    public const VENCIDO = 'VENCIDO';

    public const CANCELADO = 'CANCELADO';

    public const ESTADOS = [self::VIGENTE => 'Vigente', self::VENCIDO => 'Vencido', self::CANCELADO => 'Cancelado'];

    public const TIPOS = ['DUAL' => 'Formación dual', 'SERVICIO_SOCIAL' => 'Servicio social', 'PRACTICAS_PROFESIONALES' => 'Prácticas profesionales', 'GENERAL' => 'General'];

    // "Vence pronto": vigentes que vencen dentro de estos días.
    public const DIAS_AVISO = 30;

    protected $table = 'org_convenio';

    // estado y archivo_id no son asignables en masa.
    protected $fillable = ['org_empresa_id', 'numero', 'tipo', 'fecha_inicio', 'fecha_fin'];

    protected function casts(): array
    {
        return ['fecha_inicio' => 'date', 'fecha_fin' => 'date', 'org_empresa_id' => 'integer'];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(OrgEmpresa::class, 'org_empresa_id');
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Archivo::class, 'archivo_id');
    }

    // ── Estado efectivo ──────────────────────────────────────────────────────

    public function getEstadoEfectivoAttribute(): string
    {
        return $this->estado === self::VIGENTE && $this->fecha_fin->lt(today()) ? self::VENCIDO : $this->estado;
    }

    public function getVenceProntoAttribute(): bool
    {
        return $this->estado_efectivo === self::VIGENTE && $this->fecha_fin->lte(today()->addDays(self::DIAS_AVISO));
    }

    // Días que le quedan (0 = vence hoy); null si ya no está vigente.
    public function getDiasRestantesAttribute(): ?int
    {
        return $this->estado_efectivo === self::VIGENTE ? (int) today()->diffInDays($this->fecha_fin) : null;
    }

    public function getTipoNombreAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function estaVigente(): bool
    {
        return $this->estado_efectivo === self::VIGENTE;
    }

    // ── Filtros ──────────────────────────────────────────────────────────────

    // Por estado efectivo.
    public function scopeDeEstado(Builder $consulta, ?string $estado): Builder
    {
        return match ($estado) {
            self::VIGENTE => $consulta->where('estado', self::VIGENTE)->whereDate('fecha_fin', '>=', today()),
            self::VENCIDO => $consulta->where(fn (Builder $convenios) => $convenios->where('estado', self::VENCIDO)
                ->orWhere(fn (Builder $vencidos) => $vencidos->where('estado', self::VIGENTE)->whereDate('fecha_fin', '<', today()))),
            self::CANCELADO => $consulta->where('estado', self::CANCELADO),
            default => $consulta,
        };
    }

    public function scopeVencenPronto(Builder $consulta, int $dias = self::DIAS_AVISO): Builder
    {
        return $consulta->deEstado(self::VIGENTE)->whereDate('fecha_fin', '<=', today()->addDays($dias));
    }

    public function scopeDeTipo(Builder $consulta, ?string $tipo): Builder
    {
        return $tipo ? $consulta->where('tipo', $tipo) : $consulta;
    }

    public function scopeDeEmpresa(Builder $consulta, ?int $empresa): Builder
    {
        return $empresa ? $consulta->where('org_empresa_id', $empresa) : $consulta;
    }

    public function scopeBuscar(Builder $consulta, ?string $texto): Builder
    {
        if (blank($texto)) {
            return $consulta;
        }
        $patron = '%'.addcslashes(trim($texto), '%_\\').'%';

        return $consulta->where(fn (Builder $convenios) => $convenios->where('numero', 'ilike', $patron)
            ->orWhereHas('empresa', fn (Builder $empresas) => $empresas->where('nombre', 'ilike', $patron)));
    }

    public function scopeRecientes(Builder $consulta): Builder
    {
        return $consulta->orderByDesc('fecha_inicio')->orderByDesc('id');
    }

    // ── Operaciones ──────────────────────────────────────────────────────────

    public static function datosDe(array $datos): array
    {
        return Arr::only($datos, ['org_empresa_id', 'numero', 'tipo', 'fecha_inicio', 'fecha_fin']);
    }

    // Si ya terminó al registrarlo, queda VENCIDO desde el alta.
    public static function registrar(array $datos, UploadedFile $documento, User $usuario): self
    {
        return DB::transaction(function () use ($datos, $documento, $usuario) {
            Auditoria::motivoPorDefecto('Registro de convenio');
            $convenio = new static(static::datosDe($datos));
            $convenio->forceFill([
                'estado' => $convenio->fecha_fin->lt(today()) ? self::VENCIDO : self::VIGENTE,
                'archivo_id' => Archivo::guardar($documento, 'convenios', $usuario)->id,
            ])->save();

            return $convenio;
        });
    }

    // Solo vigente (efectivo). Un documento nuevo reemplaza al anterior, que se conserva como evidencia.
    public function actualizar(array $datos, ?UploadedFile $documento, User $usuario): void
    {
        DB::transaction(function () use ($datos, $documento, $usuario) {
            $this->bloquear();
            if (! $this->estaVigente()) {
                throw ValidationException::withMessages(['accion' => 'Solo se edita un convenio vigente; para renovarlo registra uno nuevo.']);
            }
            $this->fill(static::datosDe($datos));
            if ($documento) {
                $this->archivo_id = Archivo::guardar($documento, 'convenios', $usuario)->id;
            }
            if ($this->fecha_fin->lt(today())) {
                $this->estado = self::VENCIDO;
            }
            $this->save();
        });
    }

    // El motivo (obligatorio) lo fija el controlador con Auditoria::motivo.
    public function cancelar(): void
    {
        DB::transaction(function () {
            $this->bloquear();
            if (! $this->estaVigente()) {
                throw ValidationException::withMessages(['accion' => 'Solo se cancela un convenio vigente.']);
            }
            $this->forceFill(['estado' => self::CANCELADO])->save();
        });
    }

    /**
     * Pasa a VENCIDO los VIGENTE cuya fecha_fin ya pasó ($hoy), fila por fila (bitácora). Idempotente. Devuelve cuántos cambió.
     */
    public static function vencerPendientes(CarbonInterface $hoy): int
    {
        $cambiados = 0;
        Auditoria::conMotivo('Vencimiento automático del convenio', function () use ($hoy, &$cambiados) {
            static::where('estado', self::VIGENTE)->whereDate('fecha_fin', '<', $hoy)->orderBy('id')->each(function (self $convenio) use (&$cambiados) {
                $convenio->forceFill(['estado' => self::VENCIDO])->save();
                $cambiados++;
            });
        });

        return $cambiados;
    }

    private function bloquear(): void
    {
        static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
        $this->refresh();
    }
}
