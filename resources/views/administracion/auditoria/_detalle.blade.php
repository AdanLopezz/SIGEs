{{-- Detalle de un registro de la bitácora: campo, antes → después. --}}
<table class="table table-sm mb-0">
    <thead><tr><th scope="col">Campo</th><th scope="col">Antes</th><th scope="col"><span class="visually-hidden">cambia a</span></th><th scope="col">Después</th></tr></thead>
    <tbody>
    @foreach ($detalles as $detalle)
        <tr>
            <td class="mono">{{ $detalle->campo }}</td>
            <td>{{ $detalle->valor_anterior !== '' ? $detalle->valor_anterior : '—' }}</td>
            <td aria-hidden="true">→</td>
            <td>{{ $detalle->valor_nuevo !== '' ? $detalle->valor_nuevo : '—' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
