@extends('layouts.app')
@section('titulo', 'Resumen de seguimiento')
@section('contenido')
    <x-page-header titulo="Resumen de seguimiento" subtitulo="Casos del periodo elegido que puedes ver: por estado, por motivo, en riesgo de abandono y por grupo."
        :volver="[route('seguimiento.index'), 'Volver a Seguimiento']" />

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-4">
            <x-tarjeta titulo="Por estado" icono="bi-list-check" class="h-100">
                <dl class="dato-lista">
                    @foreach ($estados as $estado => $nombre)
                        <dt><x-badge-estado :estado="$estado" /></dt>
                        <dd><a href="{{ route('seguimiento.index', ['estado' => $estado]) }}">{{ $resumen['porEstado'][$estado] }}</a></dd>
                    @endforeach
                    <dt>Total</dt><dd><strong>{{ $resumen['total'] }}</strong></dd>
                </dl>
            </x-tarjeta>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-tarjeta titulo="Riesgo de abandono" icono="bi-exclamation-octagon" class="h-100">
                <dl class="dato-lista">
                    <dt>En riesgo (todos)</dt><dd><a href="{{ route('seguimiento.index', ['riesgo' => 1]) }}">{{ $resumen['enRiesgo'] }}</a></dd>
                    <dt>En riesgo, sin cerrar</dt><dd><strong>{{ $resumen['enRiesgoAbiertos'] }}</strong></dd>
                </dl>
            </x-tarjeta>
        </div>
        <div class="col-lg-4">
            <x-tarjeta titulo="Por motivo" icono="bi-tags" class="h-100">
                <dl class="dato-lista">
                    @foreach ($motivos as $motivo => $nombre)
                        <dt>{{ $nombre }}</dt><dd><a href="{{ route('seguimiento.index', ['motivo' => $motivo]) }}">{{ $resumen['porMotivo'][$motivo] }}</a></dd>
                    @endforeach
                </dl>
            </x-tarjeta>
        </div>
    </div>

    <x-tarjeta titulo="Por grupo" icono="bi-people" sin-relleno>
        @if ($grupos->isEmpty())
            <x-estado-vacio icono="bi-person-heart" texto="No hay casos de seguimiento en el periodo." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Grupo</th><th scope="col">Abiertos</th><th scope="col">En seguimiento</th><th scope="col">Cerrados</th><th scope="col">En riesgo</th><th scope="col">Total</th></tr></thead>
                    <tbody>
                    @foreach ($grupos as $grupo)
                        @php $fila = $resumen['porGrupo'][$grupo->id]; @endphp
                        <tr>
                            <td><a href="{{ route('seguimiento.index', ['grupo' => $grupo->id]) }}"><strong>{{ $grupo->nombre_completo }}</strong></a></td>
                            <td>{{ $fila->abiertos }}</td>
                            <td>{{ $fila->en_seguimiento }}</td>
                            <td>{{ $fila->cerrados }}</td>
                            <td>{{ $fila->en_riesgo }}</td>
                            <td><strong>{{ $fila->total }}</strong></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><span>{{ $grupos->count() }} {{ $grupos->count() === 1 ? 'grupo' : 'grupos' }} · {{ $resumen['total'] }} {{ $resumen['total'] === 1 ? 'caso' : 'casos' }}</span></div>
    </x-tarjeta>
@endsection
