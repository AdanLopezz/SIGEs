@extends('layouts.app')
@php
    $persona = $alumno->persona;
    $grupo = $inscripcion?->grupo;
    $miPersona = auth()->user()->per_persona_id;
@endphp
@section('titulo', $contexto === 'mi-escuela' ? 'Mis datos' : 'Datos del alumno')
@section('contenido')
<div class="portal">
    <x-page-header :titulo="$contexto === 'mi-escuela' ? 'Mis datos' : 'Datos del alumno'" subtitulo="Solo lectura." />
    @include('portal._encabezado')

    <x-aviso tipo="info">Para corregir un dato acude a Control Escolar.</x-aviso>

    <div class="row g-4">
        <div class="col-lg-6">
            <x-tarjeta titulo="Datos personales" icono="bi-person-vcard" class="h-100">
                <dl class="dato-lista">
                    <dt>Nombre</dt><dd>{{ $persona->nombre_completo }}</dd>
                    <dt>CURP</dt><dd class="mono">{{ $persona->curp }}</dd>
                    <dt>Correo personal</dt><dd>{{ $persona->email_personal }}</dd>
                    <dt>Teléfono</dt><dd>{{ $persona->telefono }}</dd>
                    <dt>Domicilio</dt><dd>{{ $persona->calle_numero }}, {{ $persona->colonia }}, {{ $persona->municipio }}, C.P. {{ $persona->codigo_postal }}</dd>
                </dl>
            </x-tarjeta>
        </div>
        <div class="col-lg-6">
            <x-tarjeta titulo="Datos escolares" icono="bi-mortarboard" class="h-100">
                <dl class="dato-lista">
                    <dt>Matrícula</dt><dd class="mono">{{ $alumno->matricula }}</dd>
                    <dt>Correo institucional</dt><dd>{{ $alumno->email_institucional }}</dd>
                    <dt>Carrera</dt><dd>{{ $alumno->carrera->nombre }}</dd>
                    <dt>Generación</dt><dd>{{ $alumno->generacion }}</dd>
                    <dt>Grupo</dt><dd>{{ $grupo ? $grupo->identificador.' · '.$grupo->semestre.'.º semestre · '.$grupo->turno?->nombre : 'Sin inscripción en este periodo' }}</dd>
                    <dt>Estado</dt><dd><x-badge-estado :estado="$alumno->estado->nombre" /></dd>
                </dl>
            </x-tarjeta>
        </div>
        <div class="col-lg-6">
            <x-tarjeta titulo="Tutores" icono="bi-people" class="h-100">
                {{-- El contacto (teléfono y correo) solo del tutor que consulta. --}}
                <ul class="portal-lista">
                    @foreach ($alumno->tutores as $tutor)
                        <li class="portal-item">
                            <div class="portal-item-encabezado">
                                <strong>{{ $tutor->persona->nombre_completo }}</strong>
                                @if ($tutor->es_contacto_emergencia)<x-badge-estado tono="info" texto="Contacto de emergencia" />@endif
                            </div>
                            <p class="mb-0 mt-1 texto-tenue">{{ $tutor->parentesco_nombre }}</p>
                            @if ($tutor->per_persona_id === $miPersona)
                                <p class="mb-0 mt-1">{{ $tutor->persona->telefono }} · {{ $tutor->persona->email_personal }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-tarjeta>
        </div>
        <div class="col-lg-6">
            <x-tarjeta titulo="Salud" icono="bi-heart-pulse" class="h-100">
                @if ($alumno->salud)
                    <dl class="dato-lista">
                        <dt>NSS</dt><dd class="mono">{{ $alumno->salud->nss }}</dd>
                        <dt>Tipo de sangre</dt><dd>{{ $alumno->salud->tipo_sangre }}</dd>
                        <dt>Alergias</dt><dd>{{ $alumno->salud->alergias ?: 'Ninguna registrada' }}</dd>
                        <dt>Condiciones médicas</dt><dd>{{ $alumno->salud->condiciones_medicas ?: 'Ninguna registrada' }}</dd>
                    </dl>
                @else
                    <x-estado-vacio icono="bi-heart-pulse" texto="Sin datos de salud registrados." />
                @endif
            </x-tarjeta>
        </div>
    </div>
</div>
@endsection
