<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PerPersona extends Model
{
    use Auditable;

    public const CURP_SISTEMA = 'XEXX010101HNEXXXA4';

    public const CURP_REGEX = '/^[A-Z]{4}\d{6}[HMX][A-Z]{5}[A-Z0-9]\d$/';

    public const CAMPOS_DOMICILIO = ['calle_numero', 'colonia', 'municipio', 'codigo_postal'];

    protected $table = 'per_persona';

    protected $fillable = [
        'curp', 'nombre', 'primer_apellido', 'segundo_apellido',
        'email_personal', 'telefono', 'calle_numero', 'colonia', 'municipio', 'codigo_postal',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'per_persona_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->primer_apellido} {$this->segundo_apellido}");
    }

    public function personal(): HasOne
    {
        return $this->hasOne(PerPersonal::class, 'per_persona_id');
    }

    public function alumno(): HasOne
    {
        return $this->hasOne(PerAlumno::class, 'per_persona_id');
    }

    // Datos con el domicilio de esta persona, para "mismo domicilio que el alumno".
    public function copiarDomicilioDe(array $datos): array
    {
        return array_merge($datos, $this->only(self::CAMPOS_DOMICILIO));
    }

    public function getInicialesAttribute(): string
    {
        return mb_strtoupper(mb_substr($this->nombre, 0, 1).mb_substr($this->primer_apellido, 0, 1));
    }

    public static function datosDe(array $datos): array
    {
        $datos = array_intersect_key($datos, array_flip((new static)->getFillable()));
        $datos['segundo_apellido'] = $datos['segundo_apellido'] ?? '';

        return $datos;
    }
}
