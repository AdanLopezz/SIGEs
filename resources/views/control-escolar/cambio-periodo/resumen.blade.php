@extends('layouts.app')
@use('App\Support\CambioDePeriodo')
@php
    $grupos = collect($resumen['grupos']);
    $veFichas = auth()->user()->can('alumnos.ver');
@endphp
@section('titulo', 'Cambio de periodo · Resumen')
@section('contenido')
    <x-page-header titulo="Cambio de periodo">
        <x-slot:subtitulo>De <strong>{{ $resumen['origen']['nombre'] }}</strong> ({{ $resumen['origen']['clave'] }}) a <strong>{{ $resumen['destino']['nombre'] }}</strong> ({{ $resumen['destino']['clave'] }}).</x-slot:subtitulo>
        <x-slot:acciones>
            <a href="{{ route('cambio-periodo.resumen.imprimir') }}" class="btn btn-outline-siges"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</a>
            <a href="{{ route('cambio-periodo.index') }}" class="btn btn-siges"><i class="bi bi-calendar-range" aria-hidden="true"></i> Nuevo cambio de periodo</a>
        </x-slot:acciones>
    </x-page-header>
    @include('control-escolar.cambio-periodo._pasos', ['paso' => 4])

    <x-tarjeta titulo="Resumen" icono="bi-clipboard-check">
        <dl class="cambio-periodo-totales mb-3">
            <div><dt>Grupos creados</dt><dd>{{ $grupos->where('accion', CambioDePeriodo::CREADO)->count() }}</dd></div>
            <div><dt>Reutilizados</dt><dd>{{ $grupos->where('accion', CambioDePeriodo::REUTILIZADO)->count() }}</dd></div>
            @foreach (CambioDePeriodo::RESULTADOS as $clave => $nombre)
                <div><dt>{{ $nombre }}</dt><dd>{{ $totales[$clave] }}</dd></div>
            @endforeach
        </dl>
        <dl class="dato-lista mb-0">
            <dt>Fecha de inscripción</dt><dd>{{ $resumen['fecha_inscripcion'] }}</dd>
            <dt>Confirmado</dt><dd>{{ $resumen['confirmado'] }} por {{ $resumen['usuario'] }}</dd>
            <dt>Motivo en la bitácora</dt><dd>{{ $resumen['motivo'] }}</dd>
        </dl>
    </x-tarjeta>

    <x-tarjeta titulo="Grupos en el destino" icono="bi-people-fill" sin-relleno>
        @if ($grupos->isEmpty())
            <x-estado-vacio icono="bi-people" texto="No se crearon ni reutilizaron grupos." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Grupo</th><th scope="col">Desde</th><th scope="col">Carrera y turno</th><th scope="col">Alumnos reinscritos</th><th scope="col">Acción</th></tr></thead>
                    <tbody>
                    @foreach ($grupos as $grupo)
                        <tr>
                            <td><strong>{{ $grupo['identificador'] }}</strong><small class="d-block texto-tenue">{{ $grupo['semestre'] }}.º semestre</small></td>
                            <td>{{ $grupo['origen'] ?? '—' }}</td>
                            <td>{{ $grupo['carrera'] }}<small class="d-block texto-tenue">{{ $grupo['turno'] }}</small></td>
                            <td>{{ $grupo['alumnos'] }}</td>
                            <td><x-badge-estado :estado="$grupo['accion']" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><span>Asigna las materias y los docentes en Académico → Grupos.</span></div>
    </x-tarjeta>

    <x-filtros :accion="route('cambio-periodo.resumen')" :limpiar="route('cambio-periodo.resumen')">
        <x-seleccion name="resultado" label="Resultado" col="" :opciones="CambioDePeriodo::RESULTADOS" :valor="$resultado" placeholder="Todos" />
    </x-filtros>

    <x-tarjeta sin-relleno etiqueta="Alumnos">
        @if ($alumnos->isEmpty())
            <x-estado-vacio icono="bi-mortarboard" texto="No hay alumnos con este resultado." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Alumno</th><th scope="col">Grupo origen</th><th scope="col">Grupo destino</th><th scope="col">Resultado</th><th scope="col">Motivo</th>@if ($veFichas)<th scope="col" class="text-end">Acciones</th>@endif</tr></thead>
                    <tbody>
                    @foreach ($alumnos as $alumno)
                        <tr>
                            <td><strong>{{ $alumno['nombre'] }}</strong><small class="d-block mono">{{ $alumno['matricula'] }}</small></td>
                            <td>{{ $alumno['origen'] }}</td>
                            <td>{{ $alumno['destino'] ?? '—' }}</td>
                            <td><x-badge-estado :estado="$alumno['resultado']" /></td>
                            <td>{{ $alumno['motivo'] ?? '' }}
                                @if ($alumno['resultado'] === \App\Support\CambioDePeriodo::EGRESO)
                                    @can('egreso.administrar')
                                        <a class="d-block" href="{{ route('egreso.show', $alumno['alumno_id']) }}">Revisar egreso</a>
                                    @endcan
                                @endif
                            </td>
                            @if ($veFichas)<td class="text-end"><a class="btn btn-sm btn-outline-siges" href="{{ route('alumnos.show', $alumno['alumno_id']) }}" aria-label="Ficha de {{ $alumno['nombre'] }}">Ficha</a></td>@endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$alumnos" singular="alumno" plural="alumnos" /></div>
    </x-tarjeta>
@endsection
