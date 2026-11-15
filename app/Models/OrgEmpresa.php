<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\EliminaSiNoEstaEnUso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Empresa de Vinculación (convenios, formación dual). No se borra si tiene convenios (FK → EliminaSiNoEstaEnUso).
class OrgEmpresa extends Model
{
    use Auditable, EliminaSiNoEstaEnUso;

    // Persona moral: 3 letras; persona física: 4. Luego AAMMDD y homoclave de 3.
    public const RFC_REGEX = '/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/u';

    protected $table = 'org_empresa';

    protected $fillable = ['nombre', 'rfc', 'contacto_nombre', 'contacto_telefono', 'contacto_correo'];

    public function convenios(): HasMany
    {
        return $this->hasMany(OrgConvenio::class, 'org_empresa_id');
    }

    public function scopeBuscar(Builder $consulta, ?string $texto): Builder
    {
        if (blank($texto)) {
            return $consulta;
        }
        $patron = '%'.addcslashes(trim($texto), '%_\\').'%';

        return $consulta->where(fn (Builder $empresas) => $empresas->where('nombre', 'ilike', $patron)->orWhere('rfc', 'ilike', $patron)
            ->orWhere('contacto_nombre', 'ilike', $patron)->orWhere('contacto_correo', 'ilike', $patron));
    }

    public function scopeOrdenados(Builder $consulta): Builder
    {
        return $consulta->orderBy('nombre')->orderBy('id');
    }
}
