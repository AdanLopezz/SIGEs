{{-- Últimos 10 registros del día. --}}
@if ($ultimos->isEmpty())
    <x-estado-vacio icono="bi-door-closed" texto="Todavía no hay registros hoy." />
@else
    <div class="table-responsive">
        <table class="table tabla-siges align-middle mb-0">
            <thead><tr><th scope="col">Hora</th><th scope="col">Alumno</th><th scope="col">Grupo</th><th scope="col">Movimiento</th><th scope="col">Método</th></tr></thead>
            <tbody>
            @foreach ($ultimos as $registro)
                @php $alumnoRegistro = $registro->credencial->alumno; @endphp
                <tr>
                    <td class="mono">{{ $registro->fecha_hora->format('H:i:s') }}</td>
                    <td><strong>{{ $alumnoRegistro->persona->nombre_completo }}</strong><small class="d-block mono">{{ $alumnoRegistro->matricula }}</small></td>
                    <td>{{ $alumnoRegistro->inscripcionActual?->grupo->identificador ?? '—' }}</td>
                    <td><x-badge-estado :estado="$registro->tipo_movimiento" /></td>
                    <td>{{ $registro->metodo === \App\Models\TrxRegistroAcceso::MANUAL ? 'Manual' : 'QR' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
{{-- Dentro del parcial para que se actualice junto con la tabla en cada lectura. --}}
<div class="tabla-pie"><span>{{ $ultimos->count() }} {{ $ultimos->count() === 1 ? 'registro' : 'registros' }} (se muestran los 10 más recientes)</span></div>
