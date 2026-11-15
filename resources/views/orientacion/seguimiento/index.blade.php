@extends('layouts.app')
@section('titulo', 'Seguimiento')
@section('contenido')
    <x-page-header titulo="Seguimiento" subtitulo="Casos de atención de Orientación y Tutoría de los alumnos de tus grupos. Es información confidencial: no se muestra en el portal.">
        <x-slot:acciones>
            <a href="{{ route('seguimiento.alumnos') }}" class="btn btn-outline-siges"><i class="bi bi-bell" aria-hidden="true"></i> Alumnos a atender</a>
            <a href="{{ route('seguimiento.resumen') }}" class="btn btn-outline-siges"><i class="bi bi-bar-chart" aria-hidden="true"></i> Resumen</a>
            @if ($puedeAbrir)<a href="{{ route('seguimiento.create') }}" class="btn btn-siges"><i class="bi bi-plus-lg" aria-hidden="true"></i> Abrir caso</a>@endif
        </x-slot:acciones>
    </x-page-header>

    @if ($alumnoFiltrado)
        <x-aviso tipo="info">Mostrando los casos de <strong>{{ $alumnoFiltrado->persona->nombre_completo }}</strong>. <a href="{{ route('seguimiento.index') }}">Ver todos</a>.</x-aviso>
    @endif

    <x-filtros :accion="route('seguimiento.index')" :limpiar="route('seguimiento.index')">
        @if ($alumnoFiltrado)<input type="hidden" name="alumno" value="{{ $alumnoFiltrado->id }}">@endif
        <x-seleccion name="estado" label="Estado" col="" :opciones="$estados" :valor="$filtros['estado'] ?? ''" placeholder="Todos" />
        <x-seleccion name="motivo" label="Motivo" col="" :opciones="$motivos" :valor="$filtros['motivo'] ?? ''" placeholder="Todos" />
        <x-seleccion name="grupo" label="Grupo" col="" :opciones="$grupos->pluck('nombre_completo', 'id')" :valor="$filtros['grupo'] ?? ''" placeholder="Todos" />
        <x-seleccion name="riesgo" label="Riesgo de abandono" col="" :opciones="['1' => 'Solo en riesgo']" :valor="$filtros['riesgo'] ?? ''" placeholder="Todos" />
    </x-filtros>

    <x-tarjeta sin-relleno etiqueta="Lista de casos de seguimiento">
        @if ($casos->isEmpty())
            <x-estado-vacio icono="bi-person-heart" texto="No hay casos de seguimiento con los filtros seleccionados." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Caso</th><th scope="col">Alumno</th><th scope="col">Grupo</th><th scope="col">Motivo</th><th scope="col">Estado</th><th scope="col">Notas</th><th scope="col">Última atención</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($casos as $caso)
                        @php $alumno = $caso->inscripcion->alumno; @endphp
                        <tr>
                            <td class="text-nowrap"><span class="mono">#{{ $caso->id }}</span><small class="d-block texto-tenue">{{ $caso->fecha_apertura->format('d/m/Y') }}</small></td>
                            <td><strong>{{ $alumno->persona->nombre_completo }}</strong><small class="d-block mono">{{ $alumno->matricula }}</small></td>
                            <td>{{ $caso->inscripcion->grupo->identificador }}</td>
                            <td>{{ $caso->motivo_nombre }}@if ($caso->riesgo_abandono)<span class="d-block mt-1"><x-badge-estado estado="RIESGO_ABANDONO" /></span>@endif</td>
                            <td><x-badge-estado :estado="$caso->estado" /></td>
                            <td>{{ $caso->notas_count }}</td>
                            <td>{{ $caso->ultimaNota?->fecha->format('d/m/Y') ?? '—' }}@if ($caso->ultimaNota)<small class="d-block texto-tenue">{{ $caso->ultimaNota->tipo_nombre }}</small>@endif</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-siges" href="{{ route('seguimiento.show', $caso) }}" aria-label="Ver el caso de {{ $alumno->persona->nombre_completo }}">Ver</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$casos" singular="caso" plural="casos" /></div>
    </x-tarjeta>
@endsection
