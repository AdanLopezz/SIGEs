<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\EliminaSiNoEstaEnUso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AcaDiaInhabil extends Model
{
    use Auditable;

    use EliminaSiNoEstaEnUso;

    protected $table = 'aca_dia_inhabil';

    protected $fillable = ['fecha', 'motivo'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function scopeOrdenados(Builder $consulta): Builder
    {
        return $consulta->orderBy('fecha')->orderBy('id');
    }
}
