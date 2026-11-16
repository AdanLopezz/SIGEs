@extends('layouts.app')
@section('titulo', 'Notificaciones')
@section('contenido')
    <x-page-header titulo="Notificaciones" subtitulo="Consulta la entrega de avisos y revisa los intentos de envío." />
    @if (! $habilitado)
        <div class="alert alert-warning" role="status">El envío de correos está desactivado o no tiene un transporte SMTP válido.</div>
    @endif
    <x-filtros :accion="route('notificaciones.index')" :limpiar="route('notificaciones.index')">
        <x-seleccion name="estado" label="Estado" col="" :opciones="\App\Models\ComNotificacion::ESTADOS" :valor="$filtros['estado'] ?? ''" placeholder="Todos" />
    </x-filtros>
    <x-tarjeta sin-relleno etiqueta="Avisos escolares">
        <div class="table-responsive">
            <table class="table tabla-siges align-middle mb-0">
                <thead><tr><th scope="col">Fecha</th><th scope="col">Destinatario</th><th scope="col">Canal</th><th scope="col">Estado</th><th scope="col">Historial</th><th scope="col">Detalle</th></tr></thead>
                <tbody>
                @forelse ($avisos as $aviso)
                    <tr>
                        <td>{{ $aviso->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $aviso->persona?->nombre_completo }}</td>
                        <td>{{ $aviso->canal }}</td>
                        <td>{{ \App\Models\ComNotificacion::ESTADOS[$aviso->estado] ?? $aviso->estado }}
                            @if (! $aviso->trx_incidencia_id)
                                <small class="d-block texto-tenue">Sin origen verificable; no se envía automáticamente.</small>
                            @endif
                        </td>
                        <td>{{ $aviso->intentos_count }} registros</td>
                        <td><a class="btn btn-sm btn-outline-siges" href="{{ route('notificaciones.show', $aviso) }}">Ver historial<span class="visually-hidden"> del aviso {{ $aviso->id }}</span></a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-estado-vacio icono="bi-envelope" texto="No hay avisos con estos filtros." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="tabla-pie"><x-por-pagina :paginador="$avisos" singular="aviso" plural="avisos" /></div>
    </x-tarjeta>
@endsection
