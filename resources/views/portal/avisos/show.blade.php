@extends('layouts.app')
@section('titulo', 'Aviso')
@section('contenido')
<div class="portal">
    <x-page-header :titulo="$aviso->asunto" :subtitulo="$aviso->created_at->format('d/m/Y H:i')" :volver="[route('avisos.index'), 'Volver a Avisos']" />

    <x-tarjeta>
        <p class="mb-0">{!! nl2br(e($aviso->mensaje)) !!}</p>
    </x-tarjeta>
</div>
@endsection
