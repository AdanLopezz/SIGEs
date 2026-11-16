@extends('layouts.app')
@section('titulo', 'Entrega del aviso')
@section('contenido')
    <x-page-header titulo="Entrega del aviso" subtitulo="Un envío aceptado por el servidor no garantiza que haya llegado a la bandeja de entrada." />
    <p><a href="{{ route('notificaciones.index') }}">Volver a notificaciones</a></p>
    <x-tarjeta titulo="Datos del aviso">
        <p><strong>Destinatario:</strong> {{ $notificacion->persona?->nombre_completo }}</p>
        <p><strong>Estado:</strong> {{ \App\Models\ComNotificacion::ESTADOS[$notificacion->estado] ?? $notificacion->estado }}</p>
        <p class="mb-0"><strong>Canal:</strong> {{ $notificacion->canal }}</p>
    </x-tarjeta>
    <x-tarjeta sin-relleno etiqueta="Historial de entrega">
        <div class="table-responsive">
            <table class="table tabla-siges mb-0">
                <thead><tr><th scope="col">Fecha</th><th scope="col">Aceptado por servidor</th><th scope="col">Resultado</th></tr></thead>
                <tbody>
                @forelse ($intentos as $intento)
                    <tr><td>{{ $intento->fecha_intento->format('d/m/Y H:i:s') }}</td><td>{{ $intento->exitoso ? 'Sí' : 'No confirmado' }}</td><td>{{ $intento->resultado }}</td></tr>
                @empty
                    <tr><td colspan="3">Todavía no hay intentos de envío.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="tabla-pie"><x-por-pagina :paginador="$intentos" singular="registro" plural="registros" /></div>
    </x-tarjeta>
    @if ($notificacion->canal === 'EMAIL' && $notificacion->trx_incidencia_id && in_array($notificacion->estado, ['PENDIENTE', 'FALLIDA']) && $notificacion->intentos()->exists() && ! $notificacion->intentos()->where('exitoso', true)->exists())
        <x-tarjeta titulo="Reintentar entrega">
            <p>Corrige la causa antes de reintentar. Si la entrega quedó incierta o en proceso, espera cinco minutos y verifica con el proveedor que el mensaje no fue entregado.</p>
            <form method="POST" action="{{ route('notificaciones.reintentar', $notificacion) }}">
                @csrf
                <x-errores-formulario />
                <div class="row g-3">
                    <x-campo name="motivo" label="Motivo del reintento" col="col-12" :requerido="true" maxlength="255" />
                    <div class="col-12 form-check ms-2">
                        <input class="form-check-input" type="checkbox" id="entrega_descartada" name="entrega_descartada" value="1">
                        <label class="form-check-label" for="entrega_descartada">Comprobé con el proveedor que no hubo entrega.</label>
                    </div>
                    <div class="col-12"><button class="btn btn-siges" type="submit">Programar reintento</button></div>
                </div>
            </form>
        </x-tarjeta>
    @endif
@endsection
