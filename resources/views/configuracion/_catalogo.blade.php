@extends('layouts.app')
@section('titulo', $titulo)
@section('contenido')
    <x-page-header :titulo="'Configuración · '.$titulo" subtitulo="Administra los datos del plantel y sus catálogos." />
    @include('configuracion._pestanas')
    <div class="row g-4">
        <div class="col-lg-4">
            <x-tarjeta :titulo="($registro->exists ? 'Editar ' : 'Agregar ').$singular" :icono="$registro->exists ? 'bi-pencil-square' : 'bi-plus-circle'">
                <x-errores-formulario />
                <form method="POST" action="{{ $registro->exists ? route('configuracion.'.$catalogo.'.update', $registro) : route('configuracion.'.$catalogo.'.store') }}" novalidate>
                    @csrf
                    @if($registro->exists) @method('PUT') @endif
                    <div class="row g-3">@yield('campos')</div>
                    <div class="form-acciones mt-4 mb-0">
                        @if($registro->exists)<a class="btn btn-outline-secondary" href="{{ route('configuracion.'.$catalogo.'.index') }}">Cancelar</a>@endif
                        <button type="submit" class="btn btn-siges">{{ $registro->exists ? 'Guardar cambios' : 'Agregar' }}</button>
                    </div>
                </form>
            </x-tarjeta>
        </div>
        <div class="col-lg-8">
            @yield('filtros')
            <x-tarjeta sin-relleno :etiqueta="'Lista de '.mb_strtolower($titulo)">
                @if($registros->isEmpty())
                    <x-estado-vacio icono="bi-inbox" texto="No hay registros para mostrar." />
                @else
                    <div class="table-responsive">@yield('tabla')</div>
                @endif
                <div class="tabla-pie"><x-por-pagina :paginador="$registros" singular="registro" plural="registros" /></div>
            </x-tarjeta>
        </div>
    </div>
@endsection
@push('modales')
    <x-modal id="modalConfirmarCatalogo" titulo="Confirmar acción" icono="bi-exclamation-triangle" :metodo="$cambiaEstado ? 'PATCH' : 'DELETE'" peligro confirmar>
        Vas a <span data-campo="verbo"></span> <strong data-campo="nombre"></strong>. ¿Deseas continuar?
    </x-modal>
@endpush
