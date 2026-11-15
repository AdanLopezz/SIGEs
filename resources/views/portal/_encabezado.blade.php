{{-- Encabezado del portal: alumno, grupo y navegación. Recibe $alumno, $contexto (mi-escuela | mis-hijos), $pantalla,
     $inscripcionEncabezado y $totalHijos. --}}
@php
    $pantallas = [
        'calificaciones' => ['Calificaciones', 'bi-clipboard-data'],
        'horario' => ['Horario', 'bi-calendar-week'],
        'asistencia' => ['Asistencia', 'bi-calendar-check'],
        'incidencias' => ['Incidencias', 'bi-exclamation-triangle'],
        'permisos' => ['Permisos', 'bi-door-open'],
        'credencial' => ['Credencial', 'bi-qr-code'],
        'datos' => [$contexto === 'mi-escuela' ? 'Mis datos' : 'Datos', 'bi-person-vcard'],
    ];
@endphp
<div class="portal-alumno">
    <div>
        <strong>{{ $alumno->persona->nombre_completo }}</strong>
        <small>{{ $inscripcionEncabezado?->grupo->identificador ? 'Grupo '.$inscripcionEncabezado->grupo->identificador.' · ' : '' }}Matrícula <span class="mono">{{ $alumno->matricula }}</span></small>
        @if ($periodoConsulta)<small>{{ $periodoConsulta->nombre_visible }} <span class="mono">{{ $periodoConsulta->clave }}</span>@if (\App\Support\PeriodoDeTrabajo::tipo($periodoConsulta) === \App\Support\PeriodoDeTrabajo::FUTURO) <x-badge-estado estado="PROXIMO_PERIODO" />@endif</small>@endif
    </div>
    @if ($contexto === 'mis-hijos' && $totalHijos > 1)
        <a href="{{ route('mis-hijos.seleccion.index') }}" class="btn btn-sm btn-outline-siges"><i class="bi bi-arrow-left-right" aria-hidden="true"></i> Cambiar de hijo</a>
    @endif
</div>
<nav aria-label="Secciones del portal">
    <ul class="portal-nav">
        @foreach ($pantallas as $clave => [$titulo, $icono])
            <li><a href="{{ route("{$contexto}.{$clave}.index") }}" @class(['activo' => $pantalla === $clave]) @if ($pantalla === $clave) aria-current="page" @endif><i class="bi {{ $icono }}" aria-hidden="true"></i> {{ $titulo }}</a></li>
        @endforeach
    </ul>
</nav>
