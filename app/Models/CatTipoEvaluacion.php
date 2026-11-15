<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// Catálogo de solo lectura (Parcial 1, 2, 3…). Viene de los datos iniciales.
class CatTipoEvaluacion extends Model
{
    protected $table = 'cat_tipo_evaluacion';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['orden' => 'integer', 'aplica_promedio' => 'boolean'];
    }

    public function scopeOrdenados(Builder $consulta): Builder
    {
        return $consulta->orderBy('orden')->orderBy('id');
    }
}
