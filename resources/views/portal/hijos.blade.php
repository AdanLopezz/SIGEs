@extends('layouts.app')
@section('titulo', 'Mis hijos')
@section('contenido')
<div class="portal">
    <x-page-header titulo="Mis hijos" subtitulo="Elige a quién quieres consultar. Lo recordaremos mientras dure tu sesión." />

    @if ($hijos->isEmpty())
        <x-tarjeta>
            <x-estado-vacio icono="bi-people" texto="Tu cuenta no tiene alumnos asociados como tutor. Si es un error, acude a Control Escolar." />
        </x-tarjeta>
    @else
        <div class="portal-hijos">
            @foreach ($hijos as $hijo)
                <form method="POST" action="{{ route('mis-hijos.seleccion.elegir', $hijo) }}">
                    @csrf
                    <button type="submit" @class(['portal-hijo', 'elegido' => $elegido === $hijo->id]) @if ($elegido === $hijo->id) aria-current="true" @endif>
                        <strong>{{ $hijo->persona->nombre_completo }}</strong>
                        <small>{{ $hijo->inscripcionActual ? 'Grupo '.$hijo->inscripcionActual->grupo->identificador.' · '.$hijo->inscripcionActual->grupo->turno?->nombre : 'Sin inscripción en el periodo activo' }}</small>
                        <small>Matrícula <span class="mono">{{ $hijo->matricula }}</span></small>
                        @if ($elegido === $hijo->id)<x-badge-estado tono="info" texto="Seleccionado" class="mt-2" />@endif
                    </button>
                </form>
            @endforeach
        </div>
    @endif
</div>
@endsection
