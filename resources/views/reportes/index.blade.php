@extends('layouts.app')
@section('titulo', 'Reportes')
@section('contenido')
    <x-page-header titulo="Reportes" subtitulo="Cifras del periodo de consulta de los grupos que puedes ver. Cada reporte se descarga en CSV o se imprime." />

    @if ($reportes->isEmpty())
        <x-tarjeta titulo="Reportes" icono="bi-bar-chart"><x-estado-vacio icono="bi-bar-chart" texto="Tu rol no tiene reportes disponibles." /></x-tarjeta>
    @else
        <div class="modulos-grid">
            @foreach ($reportes as $clave => $clase)
                <a href="{{ route('reportes.show', $clave) }}" class="modulo-card">
                    <i class="bi {{ $clase::ICONO }}" aria-hidden="true"></i>
                    <strong>{{ $clase::TITULO }}</strong>
                    <small>{{ $clase::DESCRIPCION }}</small>
                </a>
            @endforeach
        </div>
    @endif
@endsection
