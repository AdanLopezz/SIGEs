<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\EliminaSiNoEstaEnUso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatTipoIncidencia extends Model
{
    use Auditable;

    use EliminaSiNoEstaEnUso;

    public const CATEGORIAS = ['CONDUCTA' => 'Conducta', 'SUSPENSION' => 'Suspensión', 'INCAPACIDAD' => 'Incapacidad'];

    public const GRAVEDADES = ['LEVE' => 'Leve', 'MODERADA' => 'Moderada', 'GRAVE' => 'Grave'];

    // Estas categorías cubren varios días: fecha_fin es obligatoria.
    public const CATEGORIAS_CON_FECHA_FIN = ['SUSPENSION', 'INCAPACIDAD'];

    public $timestamps = false;

    protected $table = 'cat_tipo_incidencia';

    protected $fillable = ['nombre', 'categoria', 'nivel_gravedad', 'notifica_tutor', 'justifica_falta'];

    protected $attributes = ['nivel_gravedad' => 'LEVE', 'notifica_tutor' => true, 'justifica_falta' => false];

    protected function casts(): array
    {
        return ['notifica_tutor' => 'boolean', 'justifica_falta' => 'boolean'];
    }

    public function incidencias(): HasMany
    {
        return $this->hasMany(TrxIncidencia::class, 'cat_tipo_incidencia_id');
    }

    public function scopeOrdenados(Builder $consulta): Builder
    {
        return $consulta->orderBy('categoria')->orderBy('nombre')->orderBy('id');
    }

    public function requiereFechaFin(): bool
    {
        return in_array($this->categoria, self::CATEGORIAS_CON_FECHA_FIN, true);
    }

    public function getCategoriaNombreAttribute(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? $this->categoria;
    }

    public function getGravedadNombreAttribute(): string
    {
        return self::GRAVEDADES[$this->nivel_gravedad] ?? $this->nivel_gravedad;
    }
}
