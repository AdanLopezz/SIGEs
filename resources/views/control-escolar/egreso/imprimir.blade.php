@extends('layouts.impresion')
@section('titulo', 'Egreso')
@section('contenido')
    <div class="impresion-contenedor">
        <div class="impresion-acciones no-imprimir">
            <a class="volver mb-0" href="{{ route('egreso.index', $filtros) }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al listado</a>
            <button class="btn btn-siges" type="button" data-imprimir><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</button>
        </div>
        <article class="reporte-hoja" aria-label="Listado de egreso">
            <header>
                <strong>{{ $filtros['lista'] === 'egresados' ? 'Egresados' : 'Candidatos a egreso' }}</strong>
                <small>{{ $plantel?->nombre }} · {{ $plantel?->clave_cct }}</small>
                <small>Generado el {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->name }}</small>
            </header>
            <p class="reporte-nota">Listado de consulta. No sustituye un certificado ni acredita titulación.</p>
            <section class="reporte-seccion">
                <table>
                    <thead><tr><th>Matrícula</th><th>Alumno</th><th>Carrera</th><th>Egreso</th><th>Promedio</th></tr></thead>
                    <tbody>
                    @forelse ($alumnos as $alumno)
                        <tr><td>{{ $alumno->matricula }}</td><td>{{ $alumno->persona->nombre_completo }}</td><td>{{ $alumno->carrera->nombre }}</td><td>{{ $alumno->egreso?->fecha_egreso->format('d/m/Y') ?? 'Candidato' }}</td><td>{{ $alumno->egreso?->promedio_general ?? '—' }}</td></tr>
                    @empty
                        <tr><td colspan="5">Sin registros con los filtros seleccionados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </section>
        </article>
    </div>
@endsection
