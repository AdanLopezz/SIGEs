<?php

namespace App\Models;

use App\Support\Auditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

// La bitácora no se edita ni se borra.
class SysAuditoria extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'sys_auditoria';

    protected $fillable = ['user_id', 'tabla_afectada', 'registro_id', 'accion', 'motivo', 'ip_address'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'registro_id' => 'integer', 'user_id' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('La bitácora no se modifica.'));
        static::deleting(fn () => throw new LogicException('La bitácora no se borra.'));
    }

    // user_id no tiene FK: puede apuntar a una cuenta que ya no exista.
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(SysAuditoriaDetalle::class, 'sys_auditoria_id')->orderBy('id');
    }

    public function scopeEntreFechas(Builder $consulta, ?string $desde, ?string $hasta): Builder
    {
        return $consulta->when($desde, fn ($consulta) => $consulta->where('created_at', '>=', $desde.' 00:00:00'))
            ->when($hasta, fn ($consulta) => $consulta->where('created_at', '<=', $hasta.' 23:59:59'));
    }

    public function scopeDeUsuario(Builder $consulta, ?int $usuario): Builder
    {
        return $usuario ? $consulta->where('user_id', $usuario) : $consulta;
    }

    public function scopeDeTabla(Builder $consulta, ?string $tabla): Builder
    {
        return $tabla ? $consulta->where('tabla_afectada', $tabla) : $consulta;
    }

    public function scopeDeAccion(Builder $consulta, ?string $accion): Builder
    {
        return $accion ? $consulta->where('accion', $accion) : $consulta;
    }

    /**
     * Cambios de varios registros: [['per_alumno', 5], ['per_tutor_alumno', [3, 4]]].
     */
    public function scopeDeRegistros(Builder $consulta, array $registros): Builder
    {
        return $consulta->where(function (Builder $consulta) use ($registros) {
            foreach ($registros as [$tabla, $ids]) {
                $ids = array_filter((array) $ids);
                if ($ids) {
                    $consulta->orWhere(fn (Builder $tablas) => $tablas->where('tabla_afectada', $tabla)->whereIn('registro_id', $ids));
                }
            }
        });
    }

    public function scopeRecientes(Builder $consulta): Builder
    {
        return $consulta->orderByDesc('created_at')->orderByDesc('id');
    }

    public function getTablaNombreAttribute(): string
    {
        return Auditoria::TABLAS[$this->tabla_afectada][0] ?? $this->tabla_afectada;
    }

    public function getAccionNombreAttribute(): string
    {
        return Auditoria::ACCIONES[$this->accion] ?? $this->accion;
    }

    public function getUsuarioNombreAttribute(): string
    {
        if ($this->user_id === Auditoria::sistemaId()) {
            return 'Sistema';
        }

        return $this->usuario?->name ?? "Usuario #{$this->user_id}";
    }
}
