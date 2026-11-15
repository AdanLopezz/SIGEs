@extends('layouts.app')
@section('titulo', 'Empresas')
@section('contenido')
    <x-page-header titulo="Empresas" subtitulo="Empresas con las que el plantel tiene o tendrá convenios.">
        <x-slot:acciones>
            @can('dual.administrar')<a href="{{ route('empresas.create') }}" class="btn btn-siges"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nueva empresa</a>@endcan
        </x-slot:acciones>
    </x-page-header>

    <x-filtros :accion="route('empresas.index')" :limpiar="route('empresas.index')" boton="Buscar">
        <x-campo name="buscar" label="Buscar" col="" :value="$buscar" maxlength="100" placeholder="Nombre, RFC o contacto" />
    </x-filtros>

    <x-tarjeta sin-relleno etiqueta="Lista de empresas">
        @if ($empresas->isEmpty())
            <x-estado-vacio icono="bi-building" texto="No hay empresas con la búsqueda indicada." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Empresa</th><th scope="col">Contacto</th><th scope="col">Convenios</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($empresas as $empresa)
                        <tr>
                            <td><strong>{{ $empresa->nombre }}</strong><small class="d-block mono">{{ $empresa->rfc }}</small></td>
                            <td>{{ $empresa->contacto_nombre }}<small class="d-block texto-tenue">{{ $empresa->contacto_telefono }} · {{ $empresa->contacto_correo }}</small></td>
                            <td>{{ $empresa->convenios_count }}@if ($empresa->vigentes_count)<small class="d-block texto-tenue">{{ $empresa->vigentes_count }} {{ $empresa->vigentes_count === 1 ? 'vigente' : 'vigentes' }}</small>@endif</td>
                            <td><div class="d-flex justify-content-end gap-2">
                                <a class="btn btn-sm btn-outline-siges" href="{{ route('empresas.show', $empresa) }}" aria-label="Ver {{ $empresa->nombre }}">Ver</a>
                                @can('dual.administrar')
                                    <x-menu-acciones :etiqueta="$empresa->nombre">
                                        <li><a class="dropdown-item" href="{{ route('empresas.edit', $empresa) }}"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a></li>
                                        @if ($empresa->convenios_count === 0)
                                            <li><button type="button" class="dropdown-item peligro" data-bs-toggle="modal" data-bs-target="#modalEliminarEmpresa"
                                                data-accion="{{ route('empresas.destroy', $empresa) }}" data-nombre="{{ $empresa->nombre }}"><i class="bi bi-trash" aria-hidden="true"></i> Eliminar</button></li>
                                        @endif
                                    </x-menu-acciones>
                                @endcan
                            </div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$empresas" singular="empresa" plural="empresas" /></div>
    </x-tarjeta>
@endsection

@push('modales')
    @can('dual.administrar')
        <x-modal id="modalEliminarEmpresa" titulo="Eliminar empresa" icono="bi-trash" metodo="DELETE" boton="Eliminar" peligro confirmar>
            ¿Quieres eliminar la empresa <strong data-campo="nombre"></strong>? Solo se puede si no tiene convenios. Esta acción no se puede deshacer.
        </x-modal>
    @endcan
@endpush
