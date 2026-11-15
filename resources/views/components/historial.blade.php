<x-tarjeta {{ $attributes }} titulo="Historial de cambios" icono="bi-clock-history" sin-relleno>
    @if ($cambios->isEmpty())
        <x-estado-vacio icono="bi-clock-history" texto="Sin cambios registrados en la bitácora." />
    @else
        <div class="table-responsive">
            <table class="table tabla-siges align-middle mb-0">
                <thead><tr><th scope="col">Fecha</th><th scope="col">Usuario</th><th scope="col">Qué cambió</th><th scope="col">Motivo</th><th scope="col">Detalle</th></tr></thead>
                <tbody>
                @foreach ($cambios as $cambio)
                    <tr>
                        <td class="text-nowrap">{{ $cambio->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $cambio->usuario_nombre }}</td>
                        <td><x-badge-estado :estado="$cambio->accion" /> {{ $cambio->tabla_nombre }}</td>
                        <td>{{ $cambio->motivo }}</td>
                        <td>@if ($cambio->detalles->isNotEmpty())@include('administracion.auditoria._detalle', ['detalles' => $cambio->detalles])@else<span class="texto-tenue">—</span>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
    <div class="tabla-pie"><span>Últimos {{ $cambios->count() }} de máximo {{ $limite }} cambios</span></div>
</x-tarjeta>
