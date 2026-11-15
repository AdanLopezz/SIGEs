@extends('layouts.impresion')
@use('App\Support\Reportes\Catalogo')
@section('titulo', 'Reporte · '.$clase::TITULO)
@section('contenido')
    <div class="impresion-contenedor">
        <div class="impresion-acciones no-imprimir">
            <a href="{{ route('reportes.show', [$clase::CLAVE] + collect(request()->query())->all()) }}" class="volver mb-0"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al reporte</a>
            <button type="button" class="btn btn-siges" data-imprimir><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</button>
        </div>

        <article class="reporte-hoja" aria-label="Reporte {{ $clase::TITULO }}">
            <header>
                <strong>{{ $clase::TITULO }}</strong>
                @if ($plantel?->nombre)<small>{{ $plantel->nombre }}@if ($plantel->clave_cct) · CCT {{ $plantel->clave_cct }}@endif</small>@endif
                <small>{{ $alcance->descripcion() }}</small>
                <small>Generado el {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->name }}</small>
            </header>

            <dl class="reporte-resumen">
                @foreach ($datos['resumen'] as $indice => [$etiqueta, $valor])
                    <div><dt>{{ $etiqueta }}</dt><dd>{{ Catalogo::celda($valor, in_array($indice, $datos['porcentajesResumen'] ?? [], true)) }}</dd></div>
                @endforeach
            </dl>
            @foreach ($datos['avisos'] as $aviso)<p class="reporte-nota">{{ $aviso }}</p>@endforeach

            @foreach ($datos['secciones'] as $seccion)
                <section class="reporte-seccion">
                    <h2>{{ $seccion['titulo'] }}</h2>
                    @include('reportes._seccion', ['seccion' => $seccion])
                </section>
            @endforeach
        </article>
    </div>
@endsection
