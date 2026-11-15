<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

// Historial: otorgar o revocar es una fila nueva. El vigente es el más reciente por tutor y tipo.
class PerConsentimiento extends Model
{
    use Auditable;

    public const DATOS_PERSONALES = 'DATOS_PERSONALES';

    public const NOTIFICACIONES = 'NOTIFICACIONES';

    public const DATOS_SALUD = 'DATOS_SALUD';

    public const USO_IMAGEN = 'USO_IMAGEN';

    public const TIPOS = [
        self::DATOS_PERSONALES => 'Datos personales',
        self::NOTIFICACIONES => 'Notificaciones',
        self::DATOS_SALUD => 'Datos de salud',
        self::USO_IMAGEN => 'Uso de imagen',
    ];

    protected $table = 'per_consentimiento';

    protected $fillable = ['tipo', 'otorgado', 'fecha', 'archivo_id'];

    protected function casts(): array
    {
        return ['otorgado' => 'boolean', 'fecha' => 'date'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Los consentimientos no se modifican; registra uno nuevo.'));
        static::deleting(fn () => throw new LogicException('Los consentimientos no se borran; son historial.'));
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(PerTutorAlumno::class, 'per_tutor_alumno_id');
    }

    public function archivo(): BelongsTo
    {
        return $this->belongsTo(Archivo::class, 'archivo_id');
    }

    public function scopeRecientes(Builder $consulta): Builder
    {
        return $consulta->orderByDesc('fecha')->orderByDesc('id');
    }

    // El más reciente de cada tutor y tipo (DISTINCT ON de PostgreSQL).
    public function scopeVigentes(Builder $consulta): Builder
    {
        return $consulta->selectRaw('DISTINCT ON (per_tutor_alumno_id, tipo) per_consentimiento.*')
            ->orderBy('per_tutor_alumno_id')->orderBy('tipo')->orderByDesc('fecha')->orderByDesc('id');
    }

    /**
     * Consentimientos vigentes de varios tutores: [tutor_id][tipo] => PerConsentimiento.
     */
    public static function matrizVigentes(array $tutores): array
    {
        $matriz = [];
        foreach (static::with('archivo')->vigentes()->whereIn('per_tutor_alumno_id', $tutores ?: [0])->get() as $consentimiento) {
            $matriz[$consentimiento->per_tutor_alumno_id][$consentimiento->tipo] = $consentimiento;
        }

        return $matriz;
    }

    public function getTipoNombreAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
}
