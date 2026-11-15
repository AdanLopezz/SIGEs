<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\EliminaSiNoEstaEnUso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AcaTurno extends Model
{
    use Auditable;

    use EliminaSiNoEstaEnUso;

    protected $table = 'aca_turno';

    protected $fillable = ['nombre', 'hora_entrada', 'hora_salida', 'tolerancia_minutos', 'limite_retardo_min'];

    protected function casts(): array
    {
        return ['tolerancia_minutos' => 'integer', 'limite_retardo_min' => 'integer'];
    }

    public function scopeOrdenados(Builder $consulta): Builder
    {
        return $consulta->orderBy('nombre')->orderBy('id');
    }
}
