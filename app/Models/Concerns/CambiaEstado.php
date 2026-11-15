<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait CambiaEstado
{
    public function scopeActivos(Builder $consulta): Builder
    {
        return $consulta->where('activo', true);
    }

    public function cambiarEstado(): void
    {
        DB::transaction(function () {
            $registro = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $registro->activo = ! $registro->activo;
            $registro->save();
            $this->refresh();
        });
    }
}
