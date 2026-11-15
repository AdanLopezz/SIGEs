@extends('configuracion._catalogo')
@php
    $titulo = 'Espacios';
    $singular = 'espacio';
    $catalogo = 'espacios';
    $cambiaEstado = true;
@endphp
@section('campos')
                <x-campo name="nombre" label="Nombre" type="text" col="col-12" :value="$registro->nombre" :requerido="true" maxlength="60" />
                <x-seleccion name="tipo" label="Tipo" col="col-12" :opciones="['AULA' => 'Aula', 'LABORATORIO' => 'Laboratorio', 'TALLER' => 'Taller', 'AUDITORIO' => 'Auditorio', 'CANCHA' => 'Cancha']" :valor="$registro->tipo ?? 'AULA'" :requerido="true" />
                <x-campo name="capacidad" label="Capacidad" type="number" col="col-12" :value="$registro->capacidad ?? 40" :requerido="true" min="1" max="500" step="1" />
@endsection

@section('filtros')
    <x-filtros :accion="route('configuracion.espacios.index')" :limpiar="route('configuracion.espacios.index')">
        <x-campo name="buscar" label="Buscar" col="" :value="$filtros['buscar'] ?? ''" maxlength="100" placeholder="Nombre" />
    </x-filtros>
@endsection

@section('tabla')
    <table class="table tabla-siges align-middle mb-0">
        <thead><tr><th scope="col">Nombre</th><th scope="col">Tipo</th><th scope="col">Capacidad</th><th scope="col">Estado</th><th scope="col" class="text-end">Acciones</th></tr></thead>
        <tbody>
            @foreach($registros as $fila)
                <tr>
                    <td>{{ $fila->nombre }}</td>
                    <td><i class="bi {{ \App\Models\OrgEspacioFisico::ICONOS[$fila->tipo] ?? 'bi-building' }}" aria-hidden="true"></i> {{ ['AULA' => 'Aula', 'LABORATORIO' => 'Laboratorio', 'TALLER' => 'Taller', 'AUDITORIO' => 'Auditorio', 'CANCHA' => 'Cancha'][$fila->tipo] ?? $fila->tipo }}</td>
                    <td>{{ $fila->capacidad }}</td>

                    <td><x-badge-estado :tono="$fila->activo ? 'exito' : 'neutro'" :texto="$fila->activo ? 'Activo' : 'Inactivo'" /></td>
                    <td>@include('configuracion._acciones', ['etiqueta' => $fila->nombre])</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
