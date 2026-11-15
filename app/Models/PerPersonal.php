<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PerPersonal extends Model
{
    use Auditable;

    protected $table = 'per_personal';
    protected $fillable = ['per_persona_id', 'org_area_id', 'numero_empleado', 'puesto'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(PerPersona::class, 'per_persona_id');
    }

    // Materias de grupo en las que es el docente.
    public function materias(): HasMany
    {
        return $this->hasMany(AcaGrupoAsignatura::class, 'docente_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(OrgArea::class, 'org_area_id');
    }

    public function getNombreAttribute(): string
    {
        return $this->persona?->nombre_completo ?? '';
    }

    // Personal activo cuya persona tiene una cuenta con el rol indicado.
    public function scopeConRol(Builder $consulta, string $rol): Builder
    {
        return $consulta->where('activo', true)
            ->whereHas('persona.user', fn (Builder $usuarios) => $usuarios->role($rol, 'web'));
    }

    public function scopeOrdenados(Builder $consulta): Builder
    {
        return $consulta->orderBy(PerPersona::select(DB::raw("concat_ws(' ', nombre, primer_apellido, segundo_apellido)"))
            ->whereColumn('per_persona.id', 'per_personal.per_persona_id'))->orderBy('id');
    }

    public static function datosDe(array $datos): array
    {
        return Arr::only($datos, ['org_area_id', 'numero_empleado', 'puesto']);
    }
}
