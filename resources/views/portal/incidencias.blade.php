@extends('layouts.app')
@section('titulo', 'Incidencias')
@section('contenido')
<div class="portal">
    <x-page-header titulo="Incidencias" subtitulo="Reportes de conducta, suspensiones e incapacidades." />
    @include('portal._encabezado')

    <x-aviso tipo="info">Acude con Orientación para más detalles de cualquier incidencia.</x-aviso>

    @if ($incidencias->isEmpty())
        <x-tarjeta><x-estado-vacio icono="bi-emoji-smile" texto="Sin incidencias registradas." /></x-tarjeta>
    @else
        {{-- Sin descripción, resolución ni documento adjunto. --}}
        <ul class="portal-lista">
            @foreach ($incidencias as $incidencia)
                <li class="portal-item">
                    <div class="portal-item-encabezado">
                        <strong>{{ $incidencia->tipo->nombre }}</strong>
                        <div class="portal-badges"><x-badge-estado :estado="$incidencia->tipo->nivel_gravedad" /><x-badge-estado :estado="$incidencia->estado" /></div>
                    </div>
                    <dl class="dato-lista">
                        <dt>Tipo</dt><dd>{{ $incidencia->tipo->categoria_nombre }}</dd>
                        <dt>Fecha</dt><dd>{{ $incidencia->fecha_inicio->format('d/m/Y') }}@if ($incidencia->fecha_fin) al {{ $incidencia->fecha_fin->format('d/m/Y') }}@endif</dd>
                    </dl>
                </li>
            @endforeach
        </ul>
        <div class="tabla-pie"><x-por-pagina :paginador="$incidencias" singular="incidencia" plural="incidencias" /></div>
    @endif
</div>
@endsection
