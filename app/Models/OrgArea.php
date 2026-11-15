<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\EliminaSiNoEstaEnUso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgArea extends Model
{
    use Auditable;

    use EliminaSiNoEstaEnUso;

    public function scopeOrdenados(Builder $consulta): Builder
    {
        return $consulta->orderBy('nombre')->orderBy('id');
    }

    protected $table = 'org_area';

    protected $fillable = ['nombre'];

    public function personal(): HasMany
    {
        return $this->hasMany(PerPersonal::class, 'org_area_id');
    }
}
