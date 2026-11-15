<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// Medios de difusión de la captación de aspirantes (solo lectura; datos iniciales).
class CatMedioDifusion extends Model
{
    public $timestamps = false;

    protected $table = 'cat_medio_difusion';

    protected $fillable = ['nombre'];

    public function scopeOrdenados(Builder $consulta): Builder
    {
        return $consulta->orderBy('nombre');
    }
}
