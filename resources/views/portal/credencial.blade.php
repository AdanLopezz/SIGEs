@extends('layouts.app')
@section('titulo', 'Credencial')
@section('contenido')
<div class="portal">
    <x-page-header titulo="Credencial" subtitulo="La credencial con código QR sirve para entrar y salir del plantel." />
    @include('portal._encabezado')

    <x-tarjeta titulo="Estado de la credencial" icono="bi-qr-code">
        @if ($credencial)
            <p class="mb-2"><x-badge-estado estado="CREDENCIAL_ACTIVA" /></p>
            <p class="mb-0">Activa desde el {{ $credencial->fecha_emision->format('d/m/Y') }}.</p>
        @else
            <p class="mb-2"><x-badge-estado estado="SIN_CREDENCIAL" /></p>
            <p class="mb-0">{{ $alumno->persona->nombre }} no tiene una credencial activa.</p>
        @endif
        <p class="texto-tenue mt-3 mb-0">Para tramitarla, reponerla o reimprimirla acude a Control Escolar.</p>
    </x-tarjeta>
</div>
@endsection
