<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class SysConfiguracion extends Model
{
    use Auditable;

    protected $table = 'sys_configuracion';

    public $incrementing = false;

    protected $fillable = ['clave_cct', 'nombre', 'nombre_corto', 'calificacion_minima'];

    protected function casts(): array
    {
        return ['calificacion_minima' => 'decimal:2'];
    }

    public static function guardar(array $datos): self
    {
        // El identificador es fijo y no tiene secuencia.
        return static::unguarded(fn () => static::updateOrCreate(['id' => 1],
            array_intersect_key($datos, array_flip((new static)->getFillable()))));
    }

    public static function actual(): ?self
    {
        return static::find(1);
    }
}
