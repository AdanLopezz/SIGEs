{{-- Resultado de una lectura. Recibe $resultado (arreglo de ResultadoAcceso::aArreglo()) o null. --}}
@if ($resultado)
    @php
        $clase = ! $resultado['valido'] ? 'error' : ($resultado['alerta'] ? 'alerta' : 'ok');
        $tipo = $resultado['tipo'] === \App\Models\TrxRegistroAcceso::SALIDA ? 'SALIDA' : 'ENTRADA';
    @endphp
    <div class="acceso-resultado {{ $clase }}" role="status" aria-live="assertive" data-resultado-acceso>
        <div class="acceso-iniciales" aria-hidden="true">{{ $resultado['iniciales'] }}</div>
        <div>
            <p class="acceso-nombre">{{ $resultado['nombre'] }}</p>
            @if ($resultado['matricula'])<p class="acceso-detalle"><span class="mono">{{ $resultado['matricula'] }}</span>@if ($resultado['grupo']) · {{ $resultado['grupo'] }}@endif</p>@endif
            @if ($resultado['valido'])
                <p class="acceso-estado"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> {{ $tipo }} <small class="fs-5 fw-normal">{{ $resultado['hora'] }}</small></p>
                @if ($resultado['repetida'])<p class="acceso-nota">Lectura repetida: ya estaba registrada hace menos de un minuto.</p>@endif
                @if ($resultado['asistencia'])<p class="acceso-nota">Asistencia: {{ $resultado['asistencia'] }}</p>@endif
                @if ($resultado['aviso'])<p class="acceso-nota verde"><i class="bi bi-door-open" aria-hidden="true"></i> {{ $resultado['aviso'] }}</p>@endif
                @if ($resultado['alerta'])<p class="acceso-nota roja"><i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i> {{ $resultado['alerta'] }}</p>@endif
            @else
                <p class="acceso-estado"><i class="bi bi-x-circle-fill" aria-hidden="true"></i> NO VÁLIDA</p>
                <p class="acceso-nota roja">{{ $resultado['motivo'] }}</p>
            @endif
        </div>
    </div>
@endif
