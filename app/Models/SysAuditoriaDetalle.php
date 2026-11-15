<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

// El detalle de la bitácora no se edita ni se borra.
class SysAuditoriaDetalle extends Model
{
    public $timestamps = false;

    protected $table = 'sys_auditoria_detalle';

    protected $fillable = ['sys_auditoria_id', 'campo', 'valor_anterior', 'valor_nuevo'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('La bitácora no se modifica.'));
        static::deleting(fn () => throw new LogicException('La bitácora no se borra.'));
    }

    public function auditoria(): BelongsTo
    {
        return $this->belongsTo(SysAuditoria::class, 'sys_auditoria_id');
    }
}
