<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

// Una baja es historial: no se edita ni se borra.
class AcaBaja extends Model
{
    use Auditable;

    public const TEMPORAL = 'TEMPORAL';

    public const DEFINITIVA = 'DEFINITIVA';

    public const TIPOS = [self::TEMPORAL => 'Temporal', self::DEFINITIVA => 'Definitiva'];

    protected $table = 'aca_baja';

    protected $fillable = ['aca_inscripcion_id', 'tipo', 'fecha', 'motivo', 'registrado_por_user_id'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Las bajas no se modifican.'));
        static::deleting(fn () => throw new LogicException('Las bajas no se borran.'));
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(AcaInscripcion::class, 'aca_inscripcion_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }

    public function scopeDelPeriodo(Builder $consulta, ?int $periodo): Builder
    {
        return $periodo ? $consulta->whereHas('inscripcion.grupo', fn (Builder $grupos) => $grupos->where('aca_periodo_escolar_id', $periodo)) : $consulta;
    }

    public function scopeDeTipo(Builder $consulta, ?string $tipo): Builder
    {
        return $tipo ? $consulta->where('tipo', $tipo) : $consulta;
    }

    public function scopeEntreFechas(Builder $consulta, ?string $desde, ?string $hasta): Builder
    {
        return $consulta->when($desde, fn ($consulta) => $consulta->where('fecha', '>=', $desde))
            ->when($hasta, fn ($consulta) => $consulta->where('fecha', '<=', $hasta));
    }

    public function getTipoNombreAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
}
