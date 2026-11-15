{{-- Acciones de una fila de catálogo: Editar visible y el resto en el menú ⋮. --}}
@php
    $verbo = $cambiaEstado ? ($fila->activo ? 'desactivar' : 'activar') : 'eliminar';
    $iconoVerbo = ['desactivar' => 'bi-toggle-off', 'activar' => 'bi-toggle-on', 'eliminar' => 'bi-trash'][$verbo];
@endphp
<div class="d-flex justify-content-end gap-2">
    <a class="btn btn-sm btn-outline-siges" href="{{ route('configuracion.'.$catalogo.'.edit', $fila) }}" aria-label="Editar {{ $etiqueta }}">Editar</a>
    <x-menu-acciones :etiqueta="$etiqueta">
        @if($catalogo === 'periodos' && ! $fila->activo)
            <li><form method="POST" action="{{ route('configuracion.periodos.activar', $fila) }}">@csrf @method('PATCH')<button class="dropdown-item" type="submit"><i class="bi bi-check2-circle" aria-hidden="true"></i> Activar periodo</button></form></li>
        @endif
        @unless($catalogo === 'periodos' && $fila->activo)
            <li><button type="button" class="dropdown-item {{ $verbo === 'activar' ? '' : 'peligro' }}" data-bs-toggle="modal" data-bs-target="#modalConfirmarCatalogo"
                data-accion="{{ route('configuracion.'.$catalogo.($cambiaEstado ? '.estado' : '.destroy'), $fila) }}"
                data-nombre="{{ $etiqueta }}" data-verbo="{{ $verbo }}"><i class="bi {{ $iconoVerbo }}" aria-hidden="true"></i> {{ ucfirst($verbo) }}</button></li>
        @endunless
    </x-menu-acciones>
</div>
