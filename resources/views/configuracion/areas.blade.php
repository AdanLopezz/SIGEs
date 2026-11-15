@extends('configuracion._catalogo')
@php
    $titulo = 'Áreas';
    $singular = 'área';
    $catalogo = 'areas';
    $cambiaEstado = false;
@endphp
@section('campos')
                <x-campo name="nombre" label="Nombre" type="text" col="col-12" :value="$registro->nombre" :requerido="true" maxlength="150" />
@endsection

@section('tabla')
    <table class="table tabla-siges align-middle mb-0">
        <thead><tr><th scope="col">Nombre</th><th scope="col">Personal</th><th scope="col" class="text-end">Acciones</th></tr></thead>
        <tbody>
            @foreach($registros as $fila)
                <tr>
                    <td>{{ $fila->nombre }}</td>
                    <td>{{ $fila->personal_count }}</td>

                    <td>@include('configuracion._acciones', ['etiqueta' => $fila->nombre])</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
