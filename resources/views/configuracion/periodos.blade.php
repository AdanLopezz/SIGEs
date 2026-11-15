@extends('configuracion._catalogo')
@php
    $titulo = 'Periodos';
    $singular = 'periodo';
    $catalogo = 'periodos';
    $cambiaEstado = false;
@endphp
@section('campos')
                <x-campo name="clave" label="Clave" type="text" col="col-12" :value="$registro->clave" :requerido="true" maxlength="30" ayuda="Ej. 2026-1" class="text-uppercase" />
                <x-campo name="fecha_inicio" label="Fecha de inicio" type="date" col="col-12" :value="$registro->fecha_inicio?->format('Y-m-d')" :requerido="true" />
                <x-campo name="fecha_fin" label="Fecha de fin" type="date" col="col-12" :value="$registro->fecha_fin?->format('Y-m-d')" :requerido="true" />
@endsection

@section('tabla')
    <table class="table tabla-siges align-middle mb-0">
        <thead><tr><th scope="col">Clave</th><th scope="col">Fecha de inicio</th><th scope="col">Fecha de fin</th><th scope="col">Estado</th><th scope="col" class="text-end">Acciones</th></tr></thead>
        <tbody>
            @foreach($registros as $fila)
                <tr>
                    <td>{{ $fila->clave }}</td>
                    <td>{{ $fila->fecha_inicio?->format('d/m/Y') }}</td>
                    <td>{{ $fila->fecha_fin?->format('d/m/Y') }}</td>

                    <td><x-badge-estado :tono="$fila->activo ? 'exito' : 'neutro'" :texto="$fila->activo ? 'Activo' : 'Inactivo'" /></td>
                    <td>@include('configuracion._acciones', ['etiqueta' => $fila->clave])</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
