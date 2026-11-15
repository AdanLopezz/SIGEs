@extends('layouts.app')
@php
    $estados = ['PRESENTE' => ['presente', 'P'], 'RETARDO' => ['retardo', 'R'], 'FALTA' => ['falta', 'F'], 'JUSTIFICADA' => ['justificada', 'J']];
    $etiquetas = \App\View\Components\BadgeEstado::ESTADOS;
    // Calendario de lunes a domingo: celdas vacías antes del día 1.
    $inicio = $mes->copy()->startOfMonth();
    $vacios = $inicio->dayOfWeekIso - 1;
    $ruta = "{$contexto}.asistencia.index";
@endphp
@section('titulo', 'Asistencia')
@section('contenido')
<div class="portal">
    <x-page-header titulo="Asistencia" subtitulo="Entrada por la puerta, día por día (los días sin marca no tienen registro), y asistencia en cada materia." />
    @include('portal._encabezado')

    <x-tarjeta class="mb-4" etiqueta="Calendario de asistencia">
        <div class="portal-meses">
            @if ($anterior)
                <a href="{{ route($ruta, ['mes' => $anterior->format('Y-m')]) }}" class="btn btn-sm btn-outline-siges" aria-label="Mes anterior"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
            @else
                <span></span>
            @endif
            <strong>{{ $mes->copy()->locale('es')->translatedFormat('F Y') }}</strong>
            @if ($siguiente)
                <a href="{{ route($ruta, ['mes' => $siguiente->format('Y-m')]) }}" class="btn btn-sm btn-outline-siges" aria-label="Mes siguiente"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            @else
                <span></span>
            @endif
        </div>
        <div class="portal-calendario" role="list">
            @foreach (['L', 'M', 'M', 'J', 'V', 'S', 'D'] as $diaSemana)
                <div class="dia-semana" aria-hidden="true">{{ $diaSemana }}</div>
            @endforeach
            @for ($i = 0; $i < $vacios; $i++)
                <div class="dia vacio" aria-hidden="true"></div>
            @endfor
            @for ($dia = 1; $dia <= $mes->daysInMonth; $dia++)
                @php
                    $fecha = $inicio->copy()->day($dia);
                    $estado = $dias->get($fecha->toDateString());
                @endphp
                <div role="listitem" @class(['dia', $estados[$estado][0] ?? null => $estado !== null])
                    aria-label="{{ $fecha->copy()->locale('es')->translatedFormat('l j') }}: {{ $estado ? $etiquetas[$estado][1] : 'sin registro' }}">
                    {{ $dia }}@if ($estado)<span aria-hidden="true">{{ $estados[$estado][1] }}</span>@endif
                </div>
            @endfor
        </div>
        <div class="portal-leyenda" aria-label="Totales del mes">
            @foreach ($totales as $estado => $total)
                <x-badge-estado :estado="$estado" :texto="$etiquetas[$estado][1].' ('.$estados[$estado][1].'): '.$total" />
            @endforeach
        </div>
    </x-tarjeta>

    {{-- Pase de lista por clase: porcentaje = (asistencias + retardos) / (clases − justificadas). --}}
    <x-tarjeta titulo="Por materia" icono="bi-person-check">
        @if ($porMateria->isEmpty())
            <x-estado-vacio icono="bi-journal-bookmark" texto="Sin materias en este periodo." />
        @else
            <ul class="portal-lista">
                @foreach ($porMateria as $fila)
                    <li class="portal-item">
                        <div class="portal-item-encabezado">
                            <strong>{{ $fila->materia->asignatura->nombre }}</strong>
                            <div class="portal-badges">
                                @if ($fila->recursamiento)<x-badge-estado estado="RECURSAMIENTO" />@endif
                                @if ($fila->bajo_minimo)<x-badge-estado estado="BAJO_MINIMO" />@endif
                            </div>
                        </div>
                        @if ($fila->clases === 0)
                            <p class="texto-tenue mb-0">Sin clases registradas todavía.</p>
                        @else
                            <dl class="dato-lista">
                                <dt>Asistencia</dt><dd><strong>{{ \App\Models\TrxAsistenciaClase::formatear($fila->porcentaje) }}</strong> de {{ $fila->clases }} {{ $fila->clases === 1 ? 'clase' : 'clases' }}</dd>
                                <dt>Faltas</dt><dd>{{ $fila->faltas }}</dd>
                                <dt>Retardos</dt><dd>{{ $fila->retardos }}</dd>
                                <dt>Justificadas</dt><dd>{{ $fila->justificadas }}</dd>
                            </dl>
                        @endif
                    </li>
                @endforeach
            </ul>
            <p class="texto-tenue small mt-3 mb-0">Las faltas justificadas no cuentan en el porcentaje. Bajo {{ \App\Models\TrxAsistenciaClase::formatear($minimo) }} se avisa; acude con Control Escolar para cualquier duda.</p>
        @endif
    </x-tarjeta>
</div>
@endsection
