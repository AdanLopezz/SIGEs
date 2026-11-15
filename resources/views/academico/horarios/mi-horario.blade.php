@extends('layouts.app')
@use('App\Models\AcaHorario')
@section('titulo', 'Mi horario')
@section('contenido')
    <x-page-header titulo="Mi horario" subtitulo="Tus clases de la semana en el periodo elegido.">
        <x-slot:acciones>
            @if ($docente && $cuadricula && ! $cuadricula->vacia())
                <a href="{{ route('horarios.docente.imprimir', [$docente, 'periodo' => $periodo?->id]) }}" class="btn btn-outline-siges"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</a>
            @endif
            <a href="{{ route('pase-lista.index') }}" class="btn btn-siges"><i class="bi bi-list-check" aria-hidden="true"></i> Mis clases de hoy</a>
        </x-slot:acciones>
    </x-page-header>

    @unless ($docente)
        <x-aviso tipo="aviso">Tu cuenta no está ligada a un registro de personal activo, así que no tiene clases asignadas.</x-aviso>
    @endunless

    <x-tarjeta :titulo="$periodo ? $periodo->nombre_visible : 'Sin periodo'" icono="bi-calendar-week" sin-relleno>
        @if (! $cuadricula || $cuadricula->vacia())
            <x-estado-vacio icono="bi-calendar-week" texto="No tienes clases en el horario de este periodo." />
        @else
            @include('academico.horarios._cuadricula', ['mostrar' => ['grupo', 'aula']])
            <div class="tabla-pie"><span>{{ $cuadricula->clases->count() }} {{ $cuadricula->clases->count() === 1 ? 'clase' : 'clases' }} · {{ AcaHorario::duracion($cuadricula->clases->sum('minutos')) }} por semana</span></div>
        @endif
    </x-tarjeta>
@endsection
