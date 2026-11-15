<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

class PerAlumnoSalud extends Model
{
    use Auditable;

    // En la bitácora solo queda que el dato cambió, nunca su valor.
    protected array $camposSensibles = ['nss', 'tipo_sangre', 'alergias', 'condiciones_medicas'];

    public const TIPOS_SANGRE = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public const SIN_DATO = 'NINGUNA';

    protected $table = 'per_alumno_salud';

    protected $fillable = ['nss', 'tipo_sangre', 'alergias', 'condiciones_medicas'];

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(PerAlumno::class, 'per_alumno_id');
    }

    public static function datosDe(array $datos): array
    {
        $datos = Arr::only($datos, ['nss', 'tipo_sangre', 'alergias', 'condiciones_medicas']);
        foreach (['alergias', 'condiciones_medicas'] as $campo) {
            $datos[$campo] = filled($datos[$campo] ?? null) ? trim($datos[$campo]) : self::SIN_DATO;
        }

        return $datos;
    }
}
