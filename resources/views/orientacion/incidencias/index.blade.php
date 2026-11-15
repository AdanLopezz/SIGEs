@extends('layouts.app')
@section('titulo', 'Incidencias')
@section('contenido')
    <x-page-header titulo="Incidencias" subtitulo="Conducta, suspensiones e incapacidades de los alumnos de tus grupos.">
        <x-slot:acciones>
            @can('incidencias.registrar')<a href="{{ route('incidencias.create') }}" class="btn btn-siges"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nueva incidencia</a>@endcan
        </x-slot:acciones>
    </x-page-header>

    @if ($alumnoFiltrado)
        <x-aviso tipo="info">Mostrando las incidencias de <strong>{{ $alumnoFiltrado->persona->nombre_completo }}</strong>. <a href="{{ route('incidencias.index') }}">Ver todas</a>.</x-aviso>
    @endif

    <x-filtros :accion="route('incidencias.index')" :limpiar="route('incidencias.index')">
        @if ($alumnoFiltrado)<input type="hidden" name="alumno" value="{{ $alumnoFiltrado->id }}">@endif
        <x-seleccion name="estado" label="Estado" col="" :opciones="$estados" :valor="$filtros['estado'] ?? ''" placeholder="Todos" />
        <x-seleccion name="grupo" label="Grupo" col="" :opciones="$grupos->pluck('nombre_completo', 'id')" :valor="$filtros['grupo'] ?? ''" placeholder="Todos" />
        <x-seleccion name="categoria" label="Categoría" col="" :opciones="$categorias" :valor="$filtros['categoria'] ?? ''" placeholder="Todas" />
        <x-seleccion name="gravedad" label="Gravedad" col="" :opciones="$gravedades" :valor="$filtros['gravedad'] ?? ''" placeholder="Todas" />
        <x-campo name="desde" label="Desde" type="date" col="" :value="$filtros['desde'] ?? ''" />
        <x-campo name="hasta" label="Hasta" type="date" col="" :value="$filtros['hasta'] ?? ''" />
    </x-filtros>

    <x-tarjeta sin-relleno etiqueta="Lista de incidencias">
        @if ($incidencias->isEmpty())
            <x-estado-vacio icono="bi-exclamation-triangle" texto="No hay incidencias con los filtros seleccionados." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Fecha</th><th scope="col">Alumno</th><th scope="col">Grupo</th><th scope="col">Tipo</th><th scope="col">Gravedad</th><th scope="col">Estado</th><th scope="col">Reportó</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($incidencias as $incidencia)
                        @php $alumno = $incidencia->alumno; @endphp
                        <tr>
                            <td class="text-nowrap">{{ $incidencia->fecha_inicio->format('d/m/Y') }}@if ($incidencia->fecha_fin)<small class="d-block texto-tenue">al {{ $incidencia->fecha_fin->format('d/m/Y') }}</small>@endif</td>
                            <td>
                                @can('ver', $alumno)<a href="{{ route('alumnos.show', $alumno) }}"><strong>{{ $alumno->persona->nombre_completo }}</strong></a>@else<strong>{{ $alumno->persona->nombre_completo }}</strong>@endcan
                                <small class="d-block mono">{{ $alumno->matricula }}</small>
                            </td>
                            <td>{{ $incidencia->inscripcion->grupo->identificador }}</td>
                            <td>{{ $incidencia->tipo->nombre }}<small class="d-block texto-tenue">{{ $incidencia->tipo->categoria_nombre }}</small></td>
                            <td><x-badge-estado :estado="$incidencia->tipo->nivel_gravedad" /></td>
                            <td><x-badge-estado :estado="$incidencia->estado" /></td>
                            <td>{{ $incidencia->reportadoPor?->name }}</td>
                            <td><div class="d-flex justify-content-end gap-2">
                                <a class="btn btn-sm btn-outline-siges" href="{{ route('incidencias.show', $incidencia) }}" aria-label="Ver la incidencia de {{ $alumno->persona->nombre_completo }}">Ver</a>
                                <x-menu-acciones :etiqueta="'la incidencia de '.$alumno->persona->nombre_completo">
                                    @can('editar', $incidencia)<li><a class="dropdown-item" href="{{ route('incidencias.edit', $incidencia) }}"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a></li>@endcan
                                </x-menu-acciones>
                            </div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$incidencias" singular="incidencia" plural="incidencias" /></div>
    </x-tarjeta>
@endsection
