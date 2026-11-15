@extends('configuracion._catalogo')
@php
    $titulo = 'Días inhábiles';
    $singular = 'día inhábil';
    $catalogo = 'dias-inhabiles';
    $cambiaEstado = false;
@endphp
@section('campos')
                <x-campo name="fecha" label="Fecha" type="date" col="col-12" :value="$registro->fecha?->format('Y-m-d')" :requerido="true" />
                <x-campo name="motivo" label="Motivo" type="text" col="col-12" :value="$registro->motivo" :requerido="true" maxlength="150" />
@endsection

@section('tabla')
    <table class="table tabla-siges align-middle mb-0">
        <thead><tr><th scope="col">Fecha</th><th scope="col">Motivo</th><th scope="col" class="text-end">Acciones</th></tr></thead>
        <tbody>
            @foreach($registros as $fila)
                <tr>
                    <td>{{ $fila->fecha?->format('d/m/Y') }}</td>
                    <td>{{ $fila->motivo }}</td>


                    <td>@include('configuracion._acciones', ['etiqueta' => $fila->fecha?->format('d/m/Y').' · '.$fila->motivo])</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
