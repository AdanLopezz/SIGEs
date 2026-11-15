@extends('layouts.impresion')
@php
    $persona = $alumno->persona;
    $grupo = $alumno->inscripcionActual?->grupo;
@endphp
@section('titulo', 'Credencial · '.$persona->nombre_completo)
@section('contenido')
    <div class="impresion-contenedor">
        <div class="impresion-acciones no-imprimir">
            <a href="{{ route('alumnos.show', $alumno) }}" class="volver mb-0"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a la ficha</a>
            @if ($qr)<button type="button" class="btn btn-siges" data-imprimir><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</button>@endif
        </div>

        @if ($qr)
            <x-aviso tipo="info" class="no-imprimir mb-0">Imprímela ahora: el código QR no se guarda. Si cierras o recargas esta página, para volver a imprimirla tendrás que reponerla y esta dejará de funcionar.</x-aviso>
            <article class="credencial" aria-label="Credencial de {{ $persona->nombre_completo }}">
                <header class="credencial-encabezado">
                    <img src="{{ asset('img/logo.png') }}" alt="">
                    <div><strong>{{ $plantel?->nombre_corto ?: 'SIGEs' }}</strong><small>Credencial de alumno</small></div>
                </header>
                <div class="credencial-datos">
                    <div class="credencial-foto" aria-hidden="true">{{ $persona->iniciales }}</div>
                    <div class="credencial-texto">
                        <p class="credencial-nombre">{{ $persona->nombre_completo }}</p>
                        <dl>
                            <dt>Matrícula</dt><dd class="mono">{{ $alumno->matricula }}</dd>
                            <dt>Carrera</dt><dd>{{ $alumno->carrera->nombre }}</dd>
                            <dt>Grupo</dt><dd>{{ $grupo ? $grupo->identificador.' · '.$grupo->turno->nombre : 'Sin grupo' }}</dd>
                            <dt>Generación</dt><dd>{{ $alumno->generacion }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="credencial-qr">{!! $qr !!}<small>Escanea para verificar</small></div>
                <footer class="credencial-pie">Vigencia: {{ $periodo ? $periodo->clave.' ('.$periodo->fecha_inicio->format('d/m/Y').' – '.$periodo->fecha_fin->format('d/m/Y').')' : 'sin periodo activo' }}</footer>
            </article>
        @else
            <x-tarjeta>
                <x-estado-vacio icono="bi-qr-code" texto="Esta credencial ya no se puede volver a mostrar: el código QR solo existe al momento de emitirla. Para imprimirla de nuevo, reponla; la anterior dejará de funcionar." />
                @can('editar', $alumno)
                    @if ($alumno->estaActivo())
                        <div class="text-center"><button type="button" class="btn btn-siges" data-bs-toggle="modal" data-bs-target="#modalReponer" data-accion="{{ route('alumnos.credencial.emitir', $alumno) }}" data-nombre="{{ $persona->nombre_completo }}"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Reponer e imprimir</button></div>
                    @endif
                @endcan
            </x-tarjeta>
        @endif
    </div>
@endsection

@push('modales')
    @unless ($qr)
        <x-modal id="modalReponer" titulo="Reponer credencial" icono="bi-arrow-repeat" boton="Reponer e imprimir" peligro confirmar>
            Se emitirá una credencial nueva para <strong data-campo="nombre"></strong>. <strong>La credencial anterior dejará de funcionar.</strong>
        </x-modal>
    @endunless
@endpush
