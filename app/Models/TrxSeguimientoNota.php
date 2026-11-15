<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

// Nota de atención de un caso de seguimiento. Append-only (como la bitácora): no se edita ni se borra.
class TrxSeguimientoNota extends Model
{
    use Auditable;

    // La tabla no tiene updated_at.
    public const UPDATED_AT = null;

    public const TIPOS = [
        'ENTREVISTA_ALUMNO' => 'Entrevista con el alumno',
        'REUNION_PADRE' => 'Reunión con padre o tutor',
        'TUTORIA' => 'Tutoría',
        'CANALIZACION' => 'Canalización',
        'VISITA_DOMICILIARIA' => 'Visita domiciliaria',
        'LLAMADA' => 'Llamada',
    ];

    protected $table = 'trx_seguimiento_nota';

    protected $fillable = ['fecha', 'tipo_atencion', 'descripcion', 'acuerdos'];

    // Solo se registra el alta; descripción y acuerdos no se copian a la bitácora.
    protected array $auditarAcciones = ['INSERT'];

    protected array $camposSensibles = ['descripcion', 'acuerdos'];

    protected string $etiquetaSensible = 'texto confidencial';

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Las notas de seguimiento no se modifican.'));
        static::deleting(fn () => throw new LogicException('Las notas de seguimiento no se borran.'));
    }

    public function seguimiento(): BelongsTo
    {
        return $this->belongsTo(TrxSeguimientoAlumno::class, 'trx_seguimiento_alumno_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }

    public function getTipoNombreAttribute(): string
    {
        return self::TIPOS[$this->tipo_atencion] ?? $this->tipo_atencion;
    }
}
