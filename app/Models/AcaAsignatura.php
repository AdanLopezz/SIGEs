<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\CambiaEstado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AcaAsignatura extends Model
{
    use Auditable;

    use CambiaEstado;

    protected $table = 'aca_asignatura';

    protected $fillable = ['clave', 'nombre', 'semestre', 'horas_semana'];

    protected $attributes = ['activo' => true];

    protected function casts(): array
    {
        return ['semestre' => 'integer', 'horas_semana' => 'integer', 'activo' => 'boolean'];
    }

    public function scopeOrdenados(Builder $consulta): Builder
    {
        return $consulta->orderBy('nombre')->orderBy('id');
    }

    public function scopeBuscar(Builder $consulta, ?string $texto): Builder
    {
        if (! filled($texto)) {
            return $consulta;
        }

        return $consulta->where(function (Builder $consulta) use ($texto) {
            $consulta->where('nombre', 'ilike', '%'.trim($texto).'%');
            $consulta->orWhere('clave', 'ilike', '%'.trim($texto).'%');
        });
    }

    public function scopeDelSemestre(Builder $consulta, ?int $semestre): Builder
    {
        return $semestre ? $consulta->where('semestre', $semestre) : $consulta;
    }
}
