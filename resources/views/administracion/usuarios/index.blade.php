@extends('layouts.app')
@php $accesos = ['con' => 'Con acceso', 'sin' => 'Sin acceso']; @endphp
@section('titulo', 'Usuarios')
@section('contenido')
    <x-page-header titulo="Usuarios" subtitulo="Administra las cuentas y el acceso al plantel.">
        <x-slot:acciones><a href="{{ route('usuarios.create') }}" class="btn btn-siges"><i class="bi bi-person-plus" aria-hidden="true"></i> Nuevo usuario</a></x-slot:acciones>
    </x-page-header>

    {{-- Errores del modal Quitar acceso (el modal se cierra al recargar). --}}
    @error('motivo')<x-errores-formulario />@enderror

    <x-filtros :accion="route('usuarios.index')" :limpiar="route('usuarios.index')">
        <x-campo name="buscar" label="Buscar" col="" :value="$filtros['buscar'] ?? ''" maxlength="100" placeholder="Nombre, CURP o usuario" />
        <x-seleccion name="rol" label="Rol" col="" :opciones="$roles->combine($roles)" :valor="$filtros['rol'] ?? ''" placeholder="Todos los roles" />
        <x-seleccion name="acceso" label="Acceso" col="" :opciones="$accesos" :valor="$filtros['acceso'] ?? ''" placeholder="Todos" />
    </x-filtros>

    <x-tarjeta sin-relleno etiqueta="Lista de usuarios">
        @if ($usuarios->isEmpty())
            <x-estado-vacio icono="bi-people" texto="No se encontraron usuarios con los filtros seleccionados." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Nombre y CURP</th><th scope="col">Usuario</th><th scope="col">Área · Puesto</th><th scope="col">Roles</th><th scope="col">Acceso</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($usuarios as $usuario)
                        <tr>
                            <td><strong>{{ $usuario->name }}</strong> @if ($usuario->id === auth()->id())<span class="chip-tu">Tú</span>@endif
                                <small class="d-block mono">{{ $usuario->persona->curp }}</small></td>
                            <td>{{ $usuario->email }}</td>
                            <td>{{ $usuario->persona->personal?->area?->nombre ?? '—' }}<small class="d-block texto-tenue">{{ $usuario->persona->personal?->puesto ?? 'Sin datos laborales' }}</small></td>
                            <td>@forelse ($usuario->roles as $rol)<span class="chip">{{ $rol->name }}</span>@empty<span class="texto-tenue">Sin roles</span>@endforelse</td>
                            <td><x-badge-estado :estado="$usuario->roles->isNotEmpty() ? 'CON_ACCESO' : 'SIN_ACCESO'" /></td>
                            <td><div class="d-flex justify-content-end gap-2">
                                <a class="btn btn-sm btn-outline-siges" href="{{ route('usuarios.edit', $usuario) }}">Editar</a>
                                <x-menu-acciones :etiqueta="$usuario->name">
                                    <li><a class="dropdown-item" href="{{ route('usuarios.acceso', $usuario) }}"><i class="bi bi-key" aria-hidden="true"></i> Ver datos de acceso</a></li>
                                    @if ($usuario->roles->isNotEmpty() && $usuario->id !== auth()->id())
                                        <li><button type="button" class="dropdown-item peligro" data-bs-toggle="modal" data-bs-target="#modalQuitarAcceso" data-accion="{{ route('usuarios.quitar-acceso', $usuario) }}" data-nombre="{{ $usuario->name }}"><i class="bi bi-person-dash" aria-hidden="true"></i> Quitar acceso</button></li>
                                    @elseif ($usuario->roles->isEmpty())
                                        <li><a class="dropdown-item" href="{{ route('usuarios.edit', $usuario) }}#roles"><i class="bi bi-person-check" aria-hidden="true"></i> Dar acceso</a></li>
                                    @endif
                                </x-menu-acciones>
                            </div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$usuarios" singular="usuario" plural="usuarios" /></div>
    </x-tarjeta>
@endsection

@push('modales')
    <x-modal id="modalQuitarAcceso" titulo="Quitar acceso" icono="bi-person-dash" metodo="PATCH" boton="Quitar acceso" peligro confirmar>
        <p>¿Quieres quitar el acceso de <strong data-campo="nombre"></strong>? Su cuenta y sus datos se conservarán, pero no podrá entrar al sistema.</p>
        <div class="row g-3"><x-campo name="motivo" label="Motivo del cambio" col="col-12" maxlength="255" :requerido="true" ayuda="Queda registrado en la bitácora." /></div>
    </x-modal>
@endpush
