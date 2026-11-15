<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Solo se guarda el hash del token; el token en claro existe únicamente al imprimir.
class PerCredencialAcceso extends Model
{
    use Auditable;

    protected $table = 'per_credencial_acceso';

    protected $fillable = ['token_hash', 'fecha_emision', 'activa'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['fecha_emision' => 'datetime', 'activa' => 'boolean'];
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(PerAlumno::class, 'per_alumno_id');
    }

    public function scopeActivas(Builder $consulta): Builder
    {
        return $consulta->where('activa', true);
    }

    public static function hashDe(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function buscarPorToken(string $token): ?self
    {
        return static::where('token_hash', static::hashDe($token))->first();
    }

    public function desactivar(): void
    {
        $this->forceFill(['activa' => false])->save();
    }
}
