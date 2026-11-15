@extends('layouts.app')
@section('titulo', 'Archivo no disponible')
@section('contenido')
    <x-page-header titulo="Archivo no disponible" :subtitulo="$archivo->nombre_original" />
    <x-tarjeta>
        <x-estado-vacio icono="bi-file-earmark-x" :texto="$mensaje.' El incidente quedó registrado; avisa al administrador del sistema.'" />
    </x-tarjeta>
@endsection
