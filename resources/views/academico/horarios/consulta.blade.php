@extends('layouts.app')
@use('App\Models\AcaHorario')
@php
    $porDocente = $tipo === 'docente';
    $nombre = $porDocente ? 'Horario por docente' : 'Horario por aula';
@endphp
@section('titulo', $nombre)
@section('contenido')
    <x-page-header :titulo="$nombre" :subtitulo="$porDocente ? 'Clases de un docente en todos sus grupos del periodo.' : 'Ocupación de un aula en el periodo.'" :volver="[route('horarios.index'), 'Volver a Horarios']">
        <x-slot:acciones>
            @if ($porDocente && $elegido)
                <a href="{{ route('horarios.docente.imprimir', [$elegido, 'periodo' => $periodo?->id]) }}" class="btn btn-outline-siges"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</a>
            @endif
        </x-slot:acciones>
    </x-page-header>

    <x-filtros :accion="route($porDocente ? 'horarios.docentes' : 'horarios.aulas')" :limpiar="route($porDocente ? 'horarios.docentes' : 'horarios.aulas')">
        <x-seleccion name="periodo" label="Periodo" col="" :opciones="$periodos" :valor="$periodo?->id" />
        <x-seleccion :name="$porDocente ? 'docente' : 'aula'" :label="$porDocente ? 'Docente' : 'Aula'" col="" :opciones="$opciones" :valor="$elegido?->id"
            :placeholder="$opciones->isEmpty() ? ($porDocente ? 'Sin docentes con materias en el periodo' : 'Sin aulas') : 'Elige'" />
    </x-filtros>

    <x-tarjeta :titulo="$titulo ?? ($porDocente ? 'Elige un docente' : 'Elige un aula')" icono="bi-calendar-week" sin-relleno>
        @if (! $cuadricula)
            <x-estado-vacio :icono="$porDocente ? 'bi-person-video3' : 'bi-door-closed'" :texto="$porDocente ? 'Elige un docente para ver su horario.' : 'Elige un aula para ver su ocupación.'" />
        @elseif ($cuadricula->vacia())
            <x-estado-vacio icono="bi-calendar-week" texto="Sin clases en el periodo elegido." />
        @else
            @include('academico.horarios._cuadricula', ['mostrar' => $porDocente ? ['grupo', 'aula'] : ['grupo', 'docente']])
        @endif
        @if ($cuadricula && ! $cuadricula->vacia())
            <div class="tabla-pie"><span>{{ $cuadricula->clases->count() }} {{ $cuadricula->clases->count() === 1 ? 'clase' : 'clases' }} · {{ AcaHorario::duracion($cuadricula->clases->sum('minutos')) }} por semana</span></div>
        @endif
    </x-tarjeta>
@endsection
