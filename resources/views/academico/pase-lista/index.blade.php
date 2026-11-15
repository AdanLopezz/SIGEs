@extends('layouts.app')
@use('App\Models\AcaHorario')
@php
    $esHoy = $dia->isToday();
    $nombreDia = $dia->copy()->locale('es')->translatedFormat('l j \d\e F');
@endphp
@section('titulo', 'Mis clases de hoy')
@section('contenido')
    <x-page-header :titulo="$esHoy ? 'Mis clases de hoy' : 'Mis clases'" :subtitulo="ucfirst($nombreDia).'. Pasa lista de cada clase según tu horario.'">
        <x-slot:acciones><a href="{{ route('mi-horario.index') }}" class="btn btn-outline-siges"><i class="bi bi-calendar-week" aria-hidden="true"></i> Mi horario</a></x-slot:acciones>
    </x-page-header>

    @unless ($docente)
        <x-aviso tipo="aviso">Tu cuenta no está ligada a un registro de personal activo, así que no tiene clases asignadas.</x-aviso>
    @endunless
    @if (! $periodo)
        <x-aviso tipo="aviso">No hay un periodo escolar activo: no se pasa lista.</x-aviso>
    @elseif (! $esDiaDeClase)
        <x-aviso tipo="info">El {{ $dia->format('d/m/Y') }} no es día de clases (domingo, día inhábil o fuera del periodo activo): no se pasa lista.</x-aviso>
    @elseif (! $dentroDelPlazo)
        <x-aviso tipo="info">Puedes pasar o corregir la lista hasta {{ $diasParaCorregir }} {{ $diasParaCorregir === 1 ? 'día' : 'días' }} después de la clase. Para una fecha anterior, pide la corrección a Control Escolar.</x-aviso>
    @endif

    <x-filtros :accion="route('pase-lista.index')" :limpiar="route('pase-lista.index')">
        <x-campo name="fecha" label="Fecha" type="date" col="" :value="$dia->toDateString()" :max="today()->toDateString()" />
    </x-filtros>

    <x-tarjeta sin-relleno :etiqueta="'Clases del '.$dia->format('d/m/Y')">
        @if ($clases->isEmpty())
            <x-estado-vacio icono="bi-calendar-x" :texto="'No tienes clases los '.mb_strtolower(AcaHorario::DIAS[$dia->dayOfWeekIso] ?? 'domingos').' en el horario del periodo activo.'" />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Hora</th><th scope="col">Materia</th><th scope="col">Grupo</th><th scope="col">Aula</th><th scope="col">Lista</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($clases as $clase)
                        @php $registrada = in_array($clase->aca_grupo_asignatura_id, $registradas, true); @endphp
                        <tr>
                            <td class="mono">{{ $clase->inicio }} – {{ $clase->fin }}</td>
                            <td><strong>{{ $clase->materia->asignatura->nombre }}</strong></td>
                            <td>{{ $clase->materia->grupo->identificador }}</td>
                            <td>{{ $clase->espacio->nombre }}</td>
                            <td><x-badge-estado :estado="$registrada ? 'LISTA_REGISTRADA' : 'LISTA_PENDIENTE'" /></td>
                            <td class="text-end">
                                <a class="btn btn-sm {{ $registrada ? 'btn-outline-siges' : 'btn-siges' }}" href="{{ route('pase-lista.lista', [$clase->materia, 'fecha' => $dia->toDateString()]) }}"
                                    aria-label="{{ $registrada ? 'Ver la lista' : 'Pasar lista' }} de {{ $clase->materia->asignatura->nombre }}, grupo {{ $clase->materia->grupo->identificador }}">{{ $registrada ? 'Ver lista' : 'Pasar lista' }}</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><span>{{ $clases->count() }} {{ $clases->count() === 1 ? 'clase' : 'clases' }}</span><span>Si una materia tiene dos clases el mismo día, la lista es una sola.</span></div>
    </x-tarjeta>
@endsection
