<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Auditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Bandeja de salida. Aquí solo se crean PENDIENTE; el envío (y com_notificacion_intento) será otro módulo.
class ComNotificacion extends Model
{
    use Auditable;

    public const APP = 'APP';

    public const EMAIL = 'EMAIL';

    public const WHATSAPP = 'WHATSAPP';

    public const PENDIENTE = 'PENDIENTE';

    public const LEIDA = 'LEIDA';

    public const ESTADOS = ['PENDIENTE' => 'Pendiente', 'ENVIADA' => 'Enviada', 'FALLIDA' => 'Fallida', 'LEIDA' => 'Leída'];

    protected $table = 'com_notificacion';

    protected $fillable = ['per_persona_id', 'canal', 'asunto', 'mensaje', 'estado'];

    protected $attributes = ['estado' => self::PENDIENTE];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(PerPersona::class, 'per_persona_id');
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
        if ($this->estaLeida()) {
            return;
        }
        Auditoria::motivoPorDefecto('Lectura del aviso en el portal');
        $this->forceFill(['estado' => self::LEIDA])->save();
    }
}
