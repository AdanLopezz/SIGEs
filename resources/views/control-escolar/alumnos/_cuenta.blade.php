{{-- Botón de cuenta de acceso. Recibe $usuario (o null), $rol, $accion (ruta crear-cuenta), $datos (ruta "Datos de acceso"),
     $enMenu (ítem del menú ⋮) y, para tutores, $modal = ['usuario' => propuesto, 'nombre' => …, 'apellido' => …]:
     sin cuenta, abre #modalCuentaTutor con el usuario generado y "Regenerar". --}}
@php
    $tieneRol = $usuario?->roles->contains('name', $rol);
    $enMenu ??= false;
    $modal ??= null;
    $clase = $enMenu ? 'dropdown-item' : 'btn btn-sm '.($usuario ? 'btn-outline-siges' : 'btn-siges');
@endphp
@if ($usuario && $tieneRol)
    <a href="{{ $datos }}" class="{{ $clase }}"><i class="bi bi-key" aria-hidden="true"></i> Ver datos de acceso</a>
@elseif (! $usuario && $modal)
    <button type="button" class="{{ $clase }}" data-bs-toggle="modal" data-bs-target="#modalCuentaTutor" data-accion="{{ $accion }}"
        data-nombre="{{ $modal['nombre'].' '.$modal['apellido'] }}" data-usuario="{{ $modal['usuario'] }}"
        data-persona-nombre="{{ $modal['nombre'] }}" data-persona-apellido="{{ $modal['apellido'] }}"><i class="bi bi-person-plus" aria-hidden="true"></i> Crear cuenta</button>
@else
    <form method="POST" action="{{ $accion }}">
        @csrf
        <button type="submit" class="{{ $clase }}"><i class="bi {{ $usuario ? 'bi-person-check' : 'bi-person-plus' }}" aria-hidden="true"></i> {{ $usuario ? "Agregar rol {$rol}" : 'Crear cuenta' }}</button>
    </form>
@endif
