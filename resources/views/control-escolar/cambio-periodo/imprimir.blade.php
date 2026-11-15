@extends('layouts.impresion')
@use('App\Support\CambioDePeriodo')
@use('App\View\Components\BadgeEstado')
@section('titulo', 'Cambio de periodo · '.$resumen['origen']['clave'].' → '.$resumen['destino']['clave'])
@section('contenido')
    <div class="impresion-contenedor">
        <div class="impresion-acciones no-imprimir">
            <a href="{{ route('cambio-periodo.resumen') }}" class="volver mb-0"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al resumen</a>
            <button type="button" class="btn btn-siges" data-imprimir><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</button>
        </div>

        <article class="cambio-periodo-hoja" aria-label="Resumen del cambio de periodo">
            <header>
                <strong>Cambio de periodo y reinscripción</strong>
                <small>De {{ $resumen['origen']['nombre'] }} ({{ $resumen['origen']['clave'] }}) a {{ $resumen['destino']['nombre'] }} ({{ $resumen['destino']['clave'] }})</small>
                <small>Fecha de inscripción {{ $resumen['fecha_inscripcion'] }} · Confirmado el {{ $resumen['confirmado'] }} por {{ $resumen['usuario'] }}</small>
            </header>

            <h2>Grupos ({{ count($resumen['grupos']) }})</h2>
            <table>
                <thead><tr><th>Grupo</th><th>Desde</th><th>Semestre</th><th>Carrera</th><th>Turno</th><th>Reinscritos</th><th>Acción</th></tr></thead>
                <tbody>
                @forelse ($resumen['grupos'] as $grupo)
                    <tr><td>{{ $grupo['identificador'] }}</td><td>{{ $grupo['origen'] ?? '—' }}</td><td>{{ $grupo['semestre'] }}</td><td>{{ $grupo['carrera'] }}</td><td>{{ $grupo['turno'] }}</td><td>{{ $grupo['alumnos'] }}</td><td>{{ BadgeEstado::ESTADOS[$grupo['accion']][1] }}</td></tr>
                @empty
                    <tr><td colspan="7">Sin grupos.</td></tr>
                @endforelse
                </tbody>
            </table>

            @foreach (CambioDePeriodo::RESULTADOS as $clave => $nombre)
                @php $lista = $alumnos->where('resultado', $clave); @endphp
                <h2>{{ $nombre }} ({{ $lista->count() }})</h2>
                <table>
                    <thead><tr><th>Matrícula</th><th>Alumno</th><th>Grupo origen</th><th>{{ $clave === CambioDePeriodo::REINSCRITO ? 'Grupo destino' : 'Motivo' }}</th></tr></thead>
                    <tbody>
                    @forelse ($lista as $alumno)
                        <tr><td class="mono">{{ $alumno['matricula'] }}</td><td>{{ $alumno['nombre'] }}</td><td>{{ $alumno['origen'] }}</td><td>{{ $clave === CambioDePeriodo::REINSCRITO ? $alumno['destino'] : $alumno['motivo'] }}</td></tr>
                    @empty
                        <tr><td colspan="4">Ninguno.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            @endforeach
        </article>
    </div>
@endsection
