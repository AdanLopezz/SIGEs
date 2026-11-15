<?php

namespace App\View\Components;

use App\Models\SysAuditoria;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

// Últimos cambios de un registro (y de los relacionados) según la bitácora. Solo con auditoria.ver.
class Historial extends Component
{
    public Collection $cambios;

    /**
     * @param  array  $relacionados  [['per_persona', 3], ['per_tutor_alumno', [4, 5]]]
     */
    public function __construct(public string $tabla, public int $registro, public array $relacionados = [], public int $limite = 15)
    {
        $this->cambios = $this->shouldRender()
            ? SysAuditoria::with(['usuario', 'detalles'])->deRegistros([[$tabla, $registro], ...$relacionados])->recientes()->limit($limite)->get()
            : collect();
    }

    public function shouldRender(): bool
    {
        return auth()->user()?->can('auditoria.ver') ?? false;
    }

    public function render()
    {
        return view('components.historial');
    }
}
