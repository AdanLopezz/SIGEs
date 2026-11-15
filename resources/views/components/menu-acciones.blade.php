{{-- Menú ⋮ de acciones de una fila (como en Usuarios). El slot lleva los <li> con class="dropdown-item"; sin ítems no se muestra. --}}
@props(['etiqueta'])
@if ($slot->isNotEmpty())
    <div class="dropdown">
        <button class="btn btn-sm btn-outline-siges" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="Más acciones para {{ $etiqueta }}"><i class="bi bi-three-dots-vertical" aria-hidden="true"></i></button>
        <ul class="dropdown-menu dropdown-menu-end">{{ $slot }}</ul>
    </div>
@endif
