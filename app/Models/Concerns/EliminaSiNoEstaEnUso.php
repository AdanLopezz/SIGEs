<?php

namespace App\Models\Concerns;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

trait EliminaSiNoEstaEnUso
{
    public function eliminar(): bool
    {
        try {
            // Aísla el fallo de PostgreSQL, también dentro de otra transacción.
            return DB::transaction(fn () => (bool) $this->delete());
        } catch (QueryException $excepcion) {
            if (($excepcion->errorInfo[0] ?? $excepcion->getCode()) === '23503') {
                return false;
            }
            throw $excepcion;
        }
    }
}
