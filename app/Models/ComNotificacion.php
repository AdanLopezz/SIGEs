<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Auditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

// Bandeja compartida por el portal y el envío de correo.
class ComNotificacion extends Model
{
    use Auditable;

    public const APP = 'APP';

    public const EMAIL = 'EMAIL';

    public const WHATSAPP = 'WHATSAPP';

    public const PENDIENTE = 'PENDIENTE';

    public const LEIDA = 'LEIDA';

    public const ENVIADA = 'ENVIADA';

    public const FALLIDA = 'FALLIDA';

    public const ESTADOS = ['PENDIENTE' => 'Pendiente', 'ENVIADA' => 'Enviada', 'FALLIDA' => 'Fallida', 'LEIDA' => 'Leída'];

    protected $table = 'com_notificacion';

    protected $fillable = ['per_persona_id', 'trx_incidencia_id', 'canal', 'asunto', 'mensaje', 'estado'];

    protected $attributes = ['estado' => self::PENDIENTE];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(PerPersona::class, 'per_persona_id');
    }

    public function incidencia(): BelongsTo
    {
        return $this->belongsTo(TrxIncidencia::class, 'trx_incidencia_id');
    }

    public function intentos(): HasMany
    {
        return $this->hasMany(ComNotificacionIntento::class, 'com_notificacion_id');
    }

    public function scopePendientes(Builder $consulta): Builder
    {
        return $consulta->where('estado', self::PENDIENTE);
    }

    public function scopeDePersona(Builder $consulta, ?int $persona): Builder
    {
        return $consulta->where('per_persona_id', $persona ?? 0);
    }

    public function scopeNoLeidas(Builder $consulta): Builder
    {
        return $consulta->where('estado', '!=', self::LEIDA);
    }

    public function scopeRecientes(Builder $consulta): Builder
    {
        return $consulta->orderByDesc('created_at')->orderByDesc('id');
    }

    public function estaLeida(): bool
    {
        return $this->estado === self::LEIDA;
    }

    /**
     * Al abrirlo en el portal, LEIDA sustituye al estado de envío (no hay columna de lectura). El envío se reconstruye
     * con com_notificacion_intento y la bitácora (ENVIADA → LEIDA); una PENDIENTE leída ya no se manda por correo.
     */
    public function marcarLeida(): void
    {
        DB::transaction(function () {
            $actual = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if (! $actual->estaLeida()) {
                Auditoria::conMotivo('Lectura del aviso en el portal', fn () => $actual->forceFill(['estado' => self::LEIDA])->save());
            }
            $this->setRawAttributes($actual->getAttributes(), true);
        });
    }
}
