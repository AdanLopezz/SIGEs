@extends('layouts.app')
@section('titulo', 'Bitácora')
@section('contenido')
    <x-page-header titulo="Bitácora" subtitulo="Quién cambió qué, cuándo y por qué. Los registros no se editan ni se borran." />

    <x-filtros :accion="route('auditoria.index')" :limpiar="route('auditoria.index')">
        <x-campo name="desde" label="Desde" type="date" col="" :value="$filtros['desde']" />
        <x-campo name="hasta" label="Hasta" type="date" col="" :value="$filtros['hasta']" />
        <x-seleccion name="usuario" label="Usuario" col="" :opciones="$usuarios" :valor="$filtros['usuario'] ?? ''" placeholder="Todos" />
        <x-seleccion name="tabla" label="Tabla" col="" :opciones="$tablas" :valor="$filtros['tabla'] ?? ''" placeholder="Todas" />
        <x-seleccion name="accion" label="Acción" col="" :opciones="$acciones" :valor="$filtros['accion'] ?? ''" placeholder="Todas" />
    </x-filtros>

    <x-tarjeta sin-relleno etiqueta="Registros de la bitácora">
        @if ($registros->isEmpty())
            <x-estado-vacio icono="bi-shield-check" texto="No hay registros con los filtros seleccionados." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Fecha</th><th scope="col">Usuario</th><th scope="col">Tabla</th><th scope="col">Registro</th><th scope="col">Acción</th><th scope="col">Motivo</th><th scope="col">IP</th><th scope="col" class="text-end">Detalle</th></tr></thead>
                    <tbody>
                    @foreach ($registros as $registro)
                        <tr>
                            <td class="text-nowrap">{{ $registro->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $registro->usuario_nombre }}</td>
                            <td>{{ $registro->tabla_nombre }}<small class="d-block mono texto-tenue">{{ $registro->tabla_afectada }}</small></td>
                            <td class="mono">#{{ $registro->registro_id }}</td>
                            <td><x-badge-estado :estado="$registro->accion" /></td>
                            <td>{{ $registro->motivo }}</td>
                            <td class="mono">{{ $registro->ip_address }}</td>
                            <td class="text-end">
                                @if ($registro->detalles->isNotEmpty())
                                    <button class="btn btn-sm btn-outline-siges" type="button" data-bs-toggle="collapse" data-bs-target="#detalle-{{ $registro->id }}" aria-expanded="false" aria-controls="detalle-{{ $registro->id }}">
                                        <i class="bi bi-chevron-down" aria-hidden="true"></i> {{ $registro->detalles->count() }} {{ $registro->detalles->count() === 1 ? 'campo' : 'campos' }}
                                    </button>
                                @else
                                    <span class="texto-tenue">—</span>
                                @endif
                            </td>
                        </tr>
                        @if ($registro->detalles->isNotEmpty())
                            <tr class="collapse" id="detalle-{{ $registro->id }}">
                                <td colspan="8">@include('administracion.auditoria._detalle', ['detalles' => $registro->detalles])</td>
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$registros" singular="registro" plural="registros" /></div>
    </x-tarjeta>
@endsection
