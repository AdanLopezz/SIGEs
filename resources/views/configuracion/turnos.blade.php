@extends('configuracion._catalogo')
@php
    $titulo = 'Turnos';
    $singular = 'turno';
    $catalogo = 'turnos';
    $cambiaEstado = false;
@endphp
@section('campos')
                <x-campo name="nombre" label="Nombre" type="text" col="col-12" :value="$registro->nombre" :requerido="true" maxlength="20" />
                <x-campo name="hora_entrada" label="Hora de entrada" type="time" col="col-12" :value="$registro->hora_entrada ? substr($registro->hora_entrada, 0, 5) : ''" :requerido="true" />
                <x-campo name="hora_salida" label="Hora de salida" type="time" col="col-12" :value="$registro->hora_salida ? substr($registro->hora_salida, 0, 5) : ''" :requerido="true" />
                <x-campo name="tolerancia_minutos" label="Tolerancia (minutos)" type="number" col="col-12" :value="$registro->tolerancia_minutos ?? 10" :requerido="true" min="0" max="60" step="1" />
                <x-campo name="limite_retardo_min" label="Límite de retardo (minutos)" type="number" col="col-12" :value="$registro->limite_retardo_min ?? 30" :requerido="true" min="0" max="120" step="1" />
@endsection

@section('tabla')
    <table class="table tabla-siges align-middle mb-0">
        <thead><tr><th scope="col">Nombre</th><th scope="col">Hora de entrada</th><th scope="col">Hora de salida</th><th scope="col">Tolerancia (minutos)</th><th scope="col">Límite de retardo (minutos)</th><th scope="col" class="text-end">Acciones</th></tr></thead>
        <tbody>
            @foreach($registros as $fila)
                <tr>
                    <td>{{ $fila->nombre }}</td>
                    <td>{{ substr($fila->hora_entrada, 0, 5) }}</td>
                    <td>{{ substr($fila->hora_salida, 0, 5) }}</td>
                    <td>{{ $fila->tolerancia_minutos }}</td>
                    <td>{{ $fila->limite_retardo_min }}</td>


                    <td>@include('configuracion._acciones', ['etiqueta' => $fila->nombre])</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
