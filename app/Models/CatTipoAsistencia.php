<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Tipos de asistencia por clase (solo lectura): A Asistencia, F Falta, R Retardo y J Falta justificada.
 * El porcentaje cuenta como asistencia los tipos con cuenta_como_asistencia (A y R) y deja fuera J (ni numerador ni denominador).
 */
class CatTipoAsistencia extends Model
{
    public const ASISTENCIA = 'A';

    public const FALTA = 'F';

    public const RETARDO = 'R';

    public const JUSTIFICADA = 'J';

    // codigo => estado de <x-badge-estado> (App\View\Components\BadgeEstado).
    public const ESTADOS = [self::ASISTENCIA => 'CLASE_A', self::FALTA => 'CLASE_F', self::RETARDO => 'CLASE_R', self::JUSTIFICADA => 'CLASE_J'];

    public $timestamps = false;

    protected $table = 'cat_tipo_asistencia';

    protected $fillable = [];

    private static ?Collection $porCodigo = null;

    protected function casts(): array
    {
        return ['cuenta_como_asistencia' => 'boolean'];
    }

    // Los cuatro tipos por código (una consulta por proceso).
    public static function porCodigo(): Collection
    {
        return static::$porCodigo ??= static::orderBy('id')->get()->keyBy('codigo');
    }

    public static function idDe(string $codigo): int
    {
        return static::porCodigo()->get($codigo)?->id
            ?? throw new \RuntimeException("No existe el tipo de asistencia {$codigo} en cat_tipo_asistencia.");
    }

    public static function codigos(): array
    {
        return [self::ASISTENCIA, self::FALTA, self::RETARDO, self::JUSTIFICADA];
    }
}
