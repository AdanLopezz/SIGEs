@extends('configuracion._catalogo')
@php
    $titulo = 'Asignaturas';
    $singular = 'asignatura';
    $catalogo = 'asignaturas';
    $cambiaEstado = true;
@endphp
@section('campos')
                <x-campo name="clave" label="Clave" type="text" col="col-12" :value="$registro->clave" :requerido="true" maxlength="30" class="text-uppercase" />
                <x-campo name="nombre" label="Nombre" type="text" col="col-12" :value="$registro->nombre" :requerido="true" maxlength="150" />
                <x-campo name="semestre" label="Semestre" type="number" col="col-12" :value="$registro->semestre" :requerido="true" min="1" max="10" step="1" />
                <x-campo name="horas_semana" label="Horas por semana" type="number" col="col-12" :value="$registro->horas_semana" :requerido="true" min="1" max="40" step="1" />
@endsection

@section('filtros')
    <x-filtros :accion="route('configuracion.asignaturas.index')" :limpiar="route('configuracion.asignaturas.index')">
        <x-campo name="buscar" label="Buscar" col="" :value="$filtros['buscar'] ?? ''" maxlength="100" placeholder="Nombre o clave" />
        {{-- id propio: el formulario de la izquierda ya tiene un campo "semestre". --}}
        <x-seleccion name="semestre" id="filtroSemestre" label="Semestre" col="" :opciones="array_combine(range(1, 10), range(1, 10))" :valor="$filtros['semestre'] ?? ''" placeholder="Todos" />
    </x-filtros>
@endsection

@section('tabla')
    <table class="table tabla-siges align-middle mb-0">
        <thead><tr><th scope="col">Clave</th><th scope="col">Nombre</th><th scope="col">Semestre</th><th scope="col">Horas por semana</th><th scope="col">Estado</th><th scope="col" class="text-end">Acciones</th></tr></thead>
        <tbody>
            @foreach($registros as $fila)
                <tr>
                    <td>{{ $fila->clave }}</td>
                    <td>{{ $fila->nombre }}</td>
                    <td>{{ $fila->semestre }}</td>
                    <td>{{ $fila->horas_semana }}</td>

                    <td><x-badge-estado :tono="$fila->activo ? 'exito' : 'neutro'" :texto="$fila->activo ? 'Activa' : 'Inactiva'" /></td>
                    <td>@include('configuracion._acciones', ['etiqueta' => $fila->nombre])</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
