<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

// Evidencia de la decisión del tutor: no se edita ni se borra.
class TrxPermisoConfirmacion extends Model
{
    use Auditable;

    public const AUTORIZA = 'AUTORIZA';

    public const RECHAZA = 'RECHAZA';

    public const DECISIONES = [self::AUTORIZA => 'Autoriza', self::RECHAZA => 'Rechaza'];

    // método => [etiqueta, qué se anota como evidencia]
    public const METODOS = [
        'FIRMA' => ['Formato firmado', 'Folio del formato firmado'],
        'LLAMADA' => ['Llamada telefónica', 'Teléfono y hora de la llamada'],
        'APP' => ['Aplicación', 'Folio de la aplicación'],
    ];

    public $timestamps = false;

    protected $table = 'trx_permiso_confirmacion';

    protected $fillable = ['per_tutor_alumno_id', 'decision', 'metodo', 'evidencia', 'registrado_por_user_id', 'confirmado_at'];

    protected function casts(): array
    {
        return ['confirmado_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Las confirmaciones no se modifican.'));
        static::deleting(fn () => throw new LogicException('Las confirmaciones no se borran.'));
    }

    public function permiso(): BelongsTo
    {
        return $this->belongsTo(TrxPermisoSalida::class, 'trx_permiso_salida_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(PerTutorAlumno::class, 'per_tutor_alumno_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }
}
