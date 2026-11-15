<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\CambiaEstado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AcaCarrera extends Model
{
    use Auditable;

    use CambiaEstado;

    protected $table = 'aca_carrera';

    protected $fillable = ['clave', 'nombre', 'tipo', 'total_semestres'];

    protected $attributes = ['activo' => true];

    protected function casts(): array
    {
        return ['total_semestres' => 'integer', 'activo' => 'boolean'];
    }

    public function scopeOrdenados(Builder $consulta): Builder
    {
        return $consulta->orderBy('nombre')->orderBy('id');
    }
}
