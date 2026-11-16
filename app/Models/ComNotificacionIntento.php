<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComNotificacionIntento extends Model
{
    use Auditable;

    public const EN_PROCESO = 'En proceso; no reenviar sin comprobar la entrega.';
    public const ENVIADO = 'Aceptado por el servidor de correo.';
    public const ERROR = 'El servidor rechazó el envío; se permite reintentar.';
    public const INCIERTO = 'Entrega incierta; comprobar con el proveedor antes de reenviar.';
    public const OMITIDO = 'Omitido: el aviso ya fue leído o dejó de ser enviable.';
    public const REINTENTO = 'Reintento autorizado por administración.';

    public $timestamps = false;

    protected $table = 'com_notificacion_intento';

    protected $fillable = ['com_notificacion_id', 'fecha_intento', 'exitoso', 'resultado'];

    protected function casts(): array
    {
        return ['fecha_intento' => 'datetime', 'exitoso' => 'boolean'];
    }

    public function notificacion(): BelongsTo
    {
        return $this->belongsTo(ComNotificacion::class, 'com_notificacion_id');
    }
}
