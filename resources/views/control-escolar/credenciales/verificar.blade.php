@extends('layouts.app')
@section('titulo', 'Verificar credencial')
@section('contenido')
    <x-page-header titulo="Verificar credencial" subtitulo="Resultado de la lectura del código QR." />
    <div class="row g-4">
        <div class="col-lg-6">
            {{-- Mismo resultado en grande que el control de acceso (control-acceso.css). --}}
            <div class="acceso-resultado {{ $valida ? 'ok' : 'error' }}" role="status">
                <div>
                    <p class="acceso-estado mt-0">@if ($valida)<i class="bi bi-check-circle-fill" aria-hidden="true"></i> VÁLIDA @else<i class="bi bi-x-circle-fill" aria-hidden="true"></i> NO VÁLIDA @endif</p>
                    <p class="acceso-nota {{ $valida ? 'verde' : 'roja' }}">
                        @if ($valida) Credencial activa desde el {{ $credencial->fecha_emision->format('d/m/Y') }}.
                        @elseif (! $credencial) La credencial no existe.
                        @elseif (! $credencial->activa) La credencial fue desactivada (repuesta o por baja).
                        @else El alumno no está Activo ({{ $alumno->estado->nombre }}).
                        @endif
                    </p>
                </div>
            </div>
        </div>
        @if ($alumno)
            <div class="col-lg-6">
                <x-tarjeta titulo="Alumno" icono="bi-person-badge" class="h-100">
                    <dl class="dato-lista">
                        <dt>Nombre</dt><dd><strong>{{ $alumno->persona->nombre_completo }}</strong></dd>
                        <dt>Matrícula</dt><dd class="mono">{{ $alumno->matricula }}</dd>
                        <dt>Grupo</dt><dd>{{ $alumno->inscripcionActual?->grupo->nombre_completo ?? 'Sin grupo en el periodo activo' }}</dd>
                        <dt>Estado</dt><dd><x-badge-estado :estado="$alumno->estado->nombre" /></dd>
                    </dl>
                    @can('ver', $alumno)<a href="{{ route('alumnos.show', $alumno) }}" class="btn btn-sm btn-outline-siges mt-3">Ver ficha</a>@endcan
                </x-tarjeta>
            </div>
        @endif
    </div>
@endsection
