@extends('configuracion._catalogo')
@php
    $titulo = 'Carreras';
    $singular = 'carrera';
    $catalogo = 'carreras';
    $cambiaEstado = true;
@endphp
@section('campos')
                <x-campo name="clave" label="Clave" type="text" col="col-12" :value="$registro->clave" :requerido="true" maxlength="30" class="text-uppercase" />
                <x-campo name="nombre" label="Nombre" type="text" col="col-12" :value="$registro->nombre" :requerido="true" maxlength="150" />
                <x-seleccion name="tipo" label="Tipo" col="col-12" :opciones="['TECNICA' => 'Técnica', 'LICENCIATURA' => 'Licenciatura']" :valor="$registro->tipo ?? 'TECNICA'" :requerido="true" />
                <x-campo name="total_semestres" label="Total de semestres" type="number" col="col-12" :value="$registro->total_semestres ?? 6" :requerido="true" min="1" max="10" step="1" />
@endsection

@section('tabla')
    <table class="table tabla-siges align-middle mb-0">
        <thead><tr><th scope="col">Clave</th><th scope="col">Nombre</th><th scope="col">Tipo</th><th scope="col">Total de semestres</th><th scope="col">Estado</th><th scope="col" class="text-end">Acciones</th></tr></thead>
        <tbody>
            @foreach($registros as $fila)
                <tr>
                    <td>{{ $fila->clave }}</td>
                    <td>{{ $fila->nombre }}</td>
                    <td>{{ ['TECNICA' => 'Técnica', 'LICENCIATURA' => 'Licenciatura'][$fila->tipo] ?? $fila->tipo }}</td>
                    <td>{{ $fila->total_semestres }}</td>

                    <td><x-badge-estado :tono="$fila->activo ? 'exito' : 'neutro'" :texto="$fila->activo ? 'Activa' : 'Inactiva'" /></td>
                    <td>@include('configuracion._acciones', ['etiqueta' => $fila->nombre])</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
