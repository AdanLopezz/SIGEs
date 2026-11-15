@extends('layouts.app')
@use('App\Support\Reportes\Catalogo')
@php
    // Los enlaces de CSV e Imprimir llevan los mismos filtros (sin paginación).
    $consulta = collect(request()->query())->except(['page', 'por_pagina'])->all();
@endphp
@section('titulo', 'Reporte · '.$clase::TITULO)
@section('contenido')
    <x-page-header :titulo="$clase::TITULO" :subtitulo="$clase::DESCRIPCION" :volver="[route('reportes.index'), 'Volver a Reportes']">
        <x-slot:acciones>
            <a href="{{ route('reportes.csv', [$clase::CLAVE] + $consulta) }}" class="btn btn-outline-siges"><i class="bi bi-filetype-csv" aria-hidden="true"></i> Descargar CSV</a>
            <a href="{{ route('reportes.imprimir', [$clase::CLAVE] + $consulta) }}" class="btn btn-siges" target="_blank" rel="noopener"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</a>
        </x-slot:acciones>
    </x-page-header>

    <x-filtros :accion="route('reportes.show', $clase::CLAVE)" :limpiar="route('reportes.show', $clase::CLAVE)">
        @if ($clase::FILTROS_DE_GRUPO)
            <x-seleccion name="periodo" label="Periodo" col="" :opciones="$periodos" :valor="$alcance->periodo->id" />
        @endif
        <x-seleccion name="carrera" label="Carrera" col="" :opciones="$carreras" :valor="$filtros['carrera'] ?? ''" placeholder="Todas" />
        @if ($clase::FILTROS_DE_GRUPO)
            <x-seleccion name="turno" label="Turno" col="" :opciones="$turnos" :valor="$filtros['turno'] ?? ''" placeholder="Todos" />
            <x-seleccion name="grupo" label="Grupo" col="" :opciones="$alcance->gruposDelPeriodo->pluck('nombre_completo', 'id')" :valor="$filtros['grupo'] ?? ''" placeholder="Todos" />
        @endif
        @foreach ($clase::opciones() as $nombre => $opcion)
            <x-seleccion :name="$nombre" :label="$opcion['label']" col="" :opciones="$opcion['opciones']" :valor="$filtros[$nombre] ?? $opcion['omision']" />
        @endforeach
    </x-filtros>

    @foreach ($datos['avisos'] as $aviso)
        <x-aviso tipo="info">{{ $aviso }}</x-aviso>
    @endforeach

    <x-tarjeta titulo="Resumen" icono="bi-clipboard-data" class="mb-4">
        <dl class="reporte-resumen">
            @foreach ($datos['resumen'] as $indice => [$etiqueta, $valor])
                <div><dt>{{ $etiqueta }}</dt><dd>{{ Catalogo::celda($valor, in_array($indice, $datos['porcentajesResumen'] ?? [], true)) }}</dd></div>
            @endforeach
        </dl>
        <p class="small texto-tenue mb-0 mt-2">{{ $datos['descripcion'] ?? $alcance->descripcion() }}@if ($clase::FILTROS_DE_GRUPO) · {{ $alcance->grupos->count() }} {{ $alcance->grupos->count() === 1 ? 'grupo' : 'grupos' }}@endif</p>
    </x-tarjeta>

    @foreach ($datos['secciones'] as $seccion)
        <x-tarjeta :titulo="$seccion['titulo']" icono="bi-table" sin-relleno class="mb-4">
            @include('reportes._seccion', ['seccion' => $seccion])
            <div class="tabla-pie">
                @if ($seccion['listado'])
                    <x-por-pagina :paginador="$seccion['filas']" singular="registro" plural="registros" />
                @else
                    <span>{{ count($seccion['filas']) }} {{ count($seccion['filas']) === 1 ? 'fila' : 'filas' }}</span>
                @endif
            </div>
        </x-tarjeta>
    @endforeach
@endsection
