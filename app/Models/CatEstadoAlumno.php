<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CatEstadoAlumno extends Model
{
    public const ACTIVO = 'Activo';

    public const BAJA_TEMPORAL = 'Baja temporal';

    public const BAJA_DEFINITIVA = 'Baja definitiva';

    public const EGRESADO = 'Egresado';

    public $timestamps = false;

    protected $table = 'cat_estado_alumno';

    protected function casts(): array
    {
        return ['permite_inscripcion' => 'boolean'];
    }

    public function scopeOrdenados(Builder $consulta): Builder
    {
        return $consulta->orderBy('id');
    }

    public static function activo(): self
    {
        return static::porNombre(self::ACTIVO);
    }

    public static function porNombre(string $nombre): self
    {
        return static::where('nombre', $nombre)->firstOrFail();
    }
}
