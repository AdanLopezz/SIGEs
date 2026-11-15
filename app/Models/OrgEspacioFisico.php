<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\CambiaEstado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrgEspacioFisico extends Model
{
    use Auditable;

    use CambiaEstado;

    protected $table = 'org_espacio_fisico';

    protected $fillable = ['nombre', 'tipo', 'capacidad'];

    protected $attributes = ['activo' => true];

    protected function casts(): array
    {
        return ['capacidad' => 'integer', 'activo' => 'boolean'];
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
        });
    }

    public const ICONOS = ['AULA' => 'bi-door-closed', 'LABORATORIO' => 'bi-pc-display',
        'TALLER' => 'bi-tools', 'AUDITORIO' => 'bi-easel', 'CANCHA' => 'bi-dribbble'];
}
