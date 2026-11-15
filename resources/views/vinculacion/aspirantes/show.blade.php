@extends('layouts.app')
@use('App\Models\TrxAspirante')
@php
    $persona = $aspirante->persona;
    $administra = auth()->user()->can('aspirantes.administrar');
    $convierte = auth()->user()->can('alumnos.editar') && auth()->user()->can('alumnos.salud.ver') && $aspirante->estado === TrxAspirante::REGISTRADO;
    $errEstado = $errors->getBag('estado');
@endphp
@section('titulo', 'Aspirante · '.$persona->nombre_completo)
@section('contenido')
    <x-page-header :titulo="$persona->nombre_completo" :volver="[route('aspirantes.index'), 'Volver a Aspirantes']">
        <x-slot:subtitulo>Aspirante {{ $aspirante->ciclo_ingreso }} · <x-badge-estado :estado="$aspirante->estado" /></x-slot:subtitulo>
        <x-slot:acciones>
            @if ($convierte)<a href="{{ route('alumnos.create', ['aspirante' => $aspirante->id]) }}" class="btn btn-siges"><i class="bi bi-person-check" aria-hidden="true"></i> Convertir en alumno</a>@endif
            @if ($administra && $aspirante->esEditable())
                <a href="{{ route('aspirantes.edit', $aspirante) }}" class="btn btn-outline-siges"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a>
            @endif
            @if ($administra && $aspirante->estado !== TrxAspirante::INSCRITO)
                <button type="button" class="btn btn-outline-siges" data-bs-toggle="modal" data-bs-target="#modalEstado"><i class="bi bi-arrow-left-right" aria-hidden="true"></i> Cambiar estado</button>
            @endif
        </x-slot:acciones>
    </x-page-header>

    @if ($aspirante->estado === TrxAspirante::INSCRITO)
        <x-aviso tipo="exito">
            Ya es alumno.
            @if ($persona->alumno && auth()->user()->can('ver', $persona->alumno))<a href="{{ route('alumnos.show', $persona->alumno) }}">Ver su ficha</a>.@endif
        </x-aviso>
    @elseif ($aspirante->estado === TrxAspirante::REGISTRADO)
        <x-aviso tipo="info">Registrado: Control Escolar lo puede dar de alta como alumno con "Convertir en alumno".</x-aviso>
    @endif

    <div class="row g-4">
        <div class="col-lg-6">
            <x-tarjeta titulo="Datos personales" icono="bi-person-vcard" class="h-100">
                <dl class="dato-lista">
                    <dt>CURP</dt><dd class="mono">{{ $persona->curp }}</dd>
                    <dt>Correo</dt><dd>{{ $persona->email_personal }}</dd>
                    <dt>Teléfono</dt><dd>{{ $persona->telefono }}</dd>
                    <dt>Domicilio</dt><dd>{{ $persona->calle_numero }}, {{ $persona->colonia }}, {{ $persona->municipio }}, C. P. {{ $persona->codigo_postal }}</dd>
                </dl>
            </x-tarjeta>
        </div>
        <div class="col-lg-6">
            <x-tarjeta titulo="Interés de ingreso" icono="bi-mortarboard" class="h-100">
                <dl class="dato-lista">
                    <dt>Carrera</dt><dd>{{ $aspirante->carrera->nombre }}</dd>
                    <dt>Ciclo</dt><dd class="mono">{{ $aspirante->ciclo_ingreso }}</dd>
                    <dt>Medio de difusión</dt><dd>{{ $aspirante->medio->nombre }}</dd>
                    <dt>Escuela de procedencia</dt><dd>{{ $aspirante->escuela_procedencia }}</dd>
                    <dt>Registrado</dt><dd>{{ $aspirante->created_at?->format('d/m/Y') }}</dd>
                </dl>
            </x-tarjeta>
        </div>
    </div>
@endsection

@push('modales')
    @if ($administra && $aspirante->estado !== TrxAspirante::INSCRITO)
        <x-modal id="modalEstado" titulo="Cambiar estado" icono="bi-arrow-left-right" metodo="PATCH" :accion="route('aspirantes.estado', $aspirante)" boton="Cambiar estado"
            :data-abrir-al-cargar="$errEstado->any() ? true : null">
            <p>Estado actual: <x-badge-estado :estado="$aspirante->estado" />. Inscrito solo se alcanza al convertirlo en alumno.</p>
            <label for="estadoNuevo" class="form-label">Nuevo estado <span class="requerido" aria-hidden="true">*</span></label>
            <select id="estadoNuevo" name="estado" required class="form-select mb-3 @if ($errEstado->has('estado')) is-invalid @endif">
                @foreach ($estados as $clave => $nombre)
                    <option value="{{ $clave }}" @selected($errEstado->any() && old('estado') === $clave)>{{ $nombre }}</option>
                @endforeach
            </select>
            @if ($errEstado->has('estado'))<div class="invalid-feedback mb-2">{{ $errEstado->first('estado') }}</div>@endif
            <label for="estadoMotivo" class="form-label">Motivo <span class="requerido" aria-hidden="true">*</span></label>
            <input type="text" id="estadoMotivo" name="motivo" maxlength="255" required value="{{ $errEstado->any() ? old('motivo') : '' }}" class="form-control @if ($errEstado->has('motivo')) is-invalid @endif">
            @if ($errEstado->has('motivo'))<div class="invalid-feedback">{{ $errEstado->first('motivo') }}</div>@endif
            <div class="form-text">Obligatorio; para Descartado indica por qué. Queda en la bitácora.</div>
        </x-modal>
    @endif
@endpush
