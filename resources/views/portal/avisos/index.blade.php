@extends('layouts.app')
@section('titulo', 'Avisos')
@section('contenido')
<div class="portal">
    <x-page-header titulo="Avisos" subtitulo="Mensajes del plantel, del más reciente al más antiguo. Al abrir uno queda como leído." />

    @if ($avisos->isEmpty())
        <x-tarjeta><x-estado-vacio icono="bi-bell" texto="No tienes avisos." /></x-tarjeta>
    @else
        <ul class="portal-lista">
            @foreach ($avisos as $aviso)
                <li>
                    <a href="{{ route('avisos.show', $aviso) }}" @class(['portal-item', 'no-leido' => ! $aviso->estaLeida()])>
                        <div class="portal-item-encabezado">
                            <strong>{{ $aviso->asunto }}</strong>
                            <x-badge-estado :estado="$aviso->estaLeida() ? 'LEIDA' : 'NO_LEIDO'" />
                        </div>
                        <small class="d-block texto-tenue mt-1">{{ $aviso->created_at->format('d/m/Y H:i') }}</small>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="tabla-pie"><x-por-pagina :paginador="$avisos" singular="aviso" plural="avisos" /></div>
    @endif
</div>
@endsection
