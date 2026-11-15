@extends('layouts.app')
@php
    $esPadre = $contexto === 'mis-hijos';
    $decisiones = \App\Models\TrxPermisoConfirmacion::DECISIONES;
    $metodos = \App\Models\TrxPermisoConfirmacion::METODOS;
@endphp
@section('titulo', 'Permisos de salida')
@section('contenido')
<div class="portal">
    <x-page-header titulo="Permisos de salida" :subtitulo="$esPadre ? 'Autoriza o rechaza los permisos pendientes antes de la hora de salida.' : 'Permisos para salir antes de la hora; los confirma tu tutor.'" />
    @include('portal._encabezado')

    @if ($permisos->isEmpty())
        <x-tarjeta><x-estado-vacio icono="bi-door-open" texto="Sin permisos de salida registrados." /></x-tarjeta>
    @else
        <ul class="portal-lista">
            @foreach ($permisos as $permiso)
                @php $confirmacion = $permiso->confirmaciones->first(); @endphp
                <li class="portal-item">
                    <div class="portal-item-encabezado">
                        <strong>{{ $permiso->salida_prevista_at->format('d/m/Y H:i') }}</strong>
                        <x-badge-estado :estado="$permiso->estado" />
                    </div>
                    <dl class="dato-lista">
                        <dt>Motivo</dt><dd>{{ $permiso->motivo }}</dd>
                        @if ($confirmacion)
                            <dt>Confirmó</dt>
                            <dd>{{ $confirmacion->tutor->persona->nombre_completo }} · {{ $decisiones[$confirmacion->decision] ?? $confirmacion->decision }}
                                <small class="d-block texto-tenue">{{ $metodos[$confirmacion->metodo][0] ?? $confirmacion->metodo }} · {{ $confirmacion->confirmado_at->format('d/m/Y H:i') }}</small></dd>
                        @endif
                    </dl>
                    @if ($esPadre && $permiso->puedeConfirmarse())
                        <div class="portal-item-acciones">
                            <button type="button" class="btn btn-siges" data-bs-toggle="modal" data-bs-target="#modalAutorizar"
                                data-accion="{{ route('mis-hijos.permisos.confirmar', $permiso) }}" data-nombre="{{ $permiso->salida_prevista_at->format('d/m/Y H:i') }}"><i class="bi bi-check-lg" aria-hidden="true"></i> Autorizar</button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalRechazar"
                                data-accion="{{ route('mis-hijos.permisos.confirmar', $permiso) }}" data-nombre="{{ $permiso->salida_prevista_at->format('d/m/Y H:i') }}"><i class="bi bi-x-lg" aria-hidden="true"></i> Rechazar</button>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="tabla-pie"><x-por-pagina :paginador="$permisos" singular="permiso" plural="permisos" /></div>
    @endif
</div>
@endsection

@push('modales')
    @if ($esPadre)
        <x-modal id="modalAutorizar" titulo="Autorizar salida" icono="bi-check-lg" boton="Autorizar" confirmar>
            <input type="hidden" name="decision" value="{{ \App\Models\TrxPermisoConfirmacion::AUTORIZA }}">
            <p class="mb-0">¿Autorizas que {{ $alumno->persona->nombre }} salga el <strong data-campo="nombre"></strong>? Quedará registrado con un folio, la fecha y la hora.</p>
        </x-modal>
        <x-modal id="modalRechazar" titulo="Rechazar salida" icono="bi-x-lg" boton="Rechazar" confirmar>
            <input type="hidden" name="decision" value="{{ \App\Models\TrxPermisoConfirmacion::RECHAZA }}">
            <p class="mb-0">¿Rechazas la salida del <strong data-campo="nombre"></strong>? {{ $alumno->persona->nombre }} no podrá salir antes de la hora.</p>
        </x-modal>
    @endif
@endpush
