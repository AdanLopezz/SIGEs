@extends('layouts.app')
@section('titulo', 'Inicio')
@section('contenido')
<div class="portal">
    <x-page-header :titulo="'Hola, '.($usuario->persona?->nombre ?? $usuario->name)" subtitulo="Resumen del periodo escolar." />

    @if ($avisosSinLeer > 0)
        <x-aviso tipo="info">Tienes {{ $avisosSinLeer }} {{ $avisosSinLeer === 1 ? 'aviso sin leer' : 'avisos sin leer' }}. <a href="{{ route('avisos.index') }}">Ver avisos</a>.</x-aviso>
    @endif

    @forelse ($tarjetas as $tarjeta)
        @php
            ['alumno' => $alumnoTarjeta, 'contexto' => $contextoTarjeta, 'resumen' => $resumen] = $tarjeta;
            $grupo = $alumnoTarjeta->inscripcionActual?->grupo;
        @endphp
        <x-tarjeta :titulo="$alumnoTarjeta->persona->nombre_completo" icono="bi-mortarboard" class="mb-4">
            <x-slot:acciones>
                @if ($contextoTarjeta === 'mi-escuela')
                    <a href="{{ route('mi-escuela.calificaciones.index') }}" class="btn btn-sm btn-outline-siges">Ver detalle</a>
                @else
                    <form method="POST" action="{{ route('mis-hijos.seleccion.elegir', $alumnoTarjeta) }}">@csrf<button type="submit" class="btn btn-sm btn-outline-siges">Ver detalle</button></form>
                @endif
            </x-slot:acciones>
            <p class="texto-tenue mb-3">{{ $grupo ? 'Grupo '.$grupo->identificador.' · ' : 'Sin inscripción en el periodo activo · ' }}Matrícula <span class="mono">{{ $alumnoTarjeta->matricula }}</span></p>
            <div class="portal-resumen">
                <div class="portal-cifra"><strong>{{ $resumen['promedio'] ?? '—' }}</strong><span>{{ $resumen['promedio'] === null ? 'Promedio: sin resultados aún' : 'Promedio del periodo' }}</span></div>
                <div class="portal-cifra"><strong>{{ $resumen['faltas_mes'] }}</strong><span>{{ $resumen['faltas_mes'] === 1 ? 'Falta este mes' : 'Faltas este mes' }}</span></div>
                <div class="portal-cifra"><strong>{{ $resumen['incidencias_abiertas'] }}</strong><span>Incidencias abiertas</span></div>
                <div class="portal-cifra"><strong>{{ $resumen['permisos_pendientes'] }}</strong><span>Permisos pendientes</span></div>
            </div>
        </x-tarjeta>
    @empty
        <x-tarjeta>
            <x-estado-vacio icono="bi-mortarboard" texto="Tu cuenta aún no tiene alumnos asociados. Acude a Control Escolar." />
        </x-tarjeta>
    @endforelse
</div>
@endsection
