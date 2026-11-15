@extends('layouts.app')
@use('App\Support\CambioDePeriodo')
@php
    $omitidos = $totales[CambioDePeriodo::NO_PASA] + $totales[CambioDePeriodo::YA_INSCRITO] + $totales[CambioDePeriodo::PASA] + $totales[CambioDePeriodo::AVISO] - $marcados;
@endphp
@section('titulo', 'Cambio de periodo · Alumnos')
@section('contenido')
    <x-page-header titulo="Cambio de periodo" :volver="[route('cambio-periodo.grupos', ['origen' => $cambio->origen->id, 'destino' => $cambio->destino->id]), 'Volver a Grupos']">
        <x-slot:subtitulo>De <strong>{{ $cambio->origen->nombre_visible }}</strong> ({{ $cambio->origen->clave }}) a <strong>{{ $cambio->destino->nombre_visible }}</strong> ({{ $cambio->destino->clave }}).</x-slot:subtitulo>
    </x-page-header>
    @include('control-escolar.cambio-periodo._pasos', ['paso' => 3])
    <x-errores-formulario />

    @if ($sinCerrar > 0)
        <x-aviso tipo="aviso">El periodo origen tiene <strong>{{ $sinCerrar }} {{ $sinCerrar === 1 ? 'materia' : 'materias' }} sin cerrar</strong> (alumnos sin resultado): los adeudos pueden estar incompletos. Puedes continuar; al confirmar se te pedirá que lo reconozcas.</x-aviso>
    @endif

    <x-tarjeta titulo="Resumen" icono="bi-list-check">
        <dl class="cambio-periodo-totales">
            <div><dt>Grupos por crear</dt><dd>{{ $porCrear }}</dd></div>
            <div><dt>Por reutilizar</dt><dd>{{ $porReutilizar }}</dd></div>
            @foreach (CambioDePeriodo::SITUACIONES as $clave => $nombre)
                <div><dt><x-badge-estado :estado="$clave" /></dt><dd>{{ $totales[$clave] }}</dd></div>
            @endforeach
            <div><dt>Marcados para reinscribir</dt><dd>{{ $marcados }}</dd></div>
        </dl>
        <p class="texto-tenue small mt-2 mb-0">PASA va marcado; AVISO (debe más de {{ \App\Models\TrxResultadoAsignatura::maximoReprobadas() }} materias) va desmarcado y lo decides tú. NO PASA, ÚLTIMO SEMESTRE y YA INSCRITO no se reinscriben. Usa «Revisar» para cambiar casillas o grupos destino.</p>
    </x-tarjeta>

    <x-tarjeta sin-relleno etiqueta="Alumnos por grupo">
        @if ($grupos->isEmpty())
            <x-estado-vacio icono="bi-people" texto="El periodo origen no tiene grupos." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Grupo origen</th><th scope="col">Destino</th><th scope="col">Alumnos</th><th scope="col">Pasa</th><th scope="col">Aviso</th><th scope="col">No pasa</th><th scope="col">Último sem.</th><th scope="col">Ya inscritos</th><th scope="col">Marcados</th><th scope="col">Sin cerrar</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($grupos as $fila)
                        <tr>
                            <td><strong>{{ $fila->grupo->identificador }}</strong><small class="d-block texto-tenue">{{ $fila->grupo->semestre }}.º · {{ $fila->grupo->carrera->nombre }} · {{ $fila->grupo->turno->nombre }}</small></td>
                            <td>@if ($fila->destino)<strong>{{ $fila->destino }}</strong>@else<x-badge-estado estado="ULTIMO_SEMESTRE" />@endif</td>
                            <td>{{ $fila->alumnos }}</td>
                            @foreach ([CambioDePeriodo::PASA, CambioDePeriodo::AVISO, CambioDePeriodo::NO_PASA, CambioDePeriodo::ULTIMO_SEMESTRE, CambioDePeriodo::YA_INSCRITO] as $clave)
                                <td>{{ $fila->situaciones[$clave] }}</td>
                            @endforeach
                            <td><strong>{{ $fila->marcados }}</strong></td>
                            <td>@if ($fila->sin_cerrar > 0)<x-badge-estado tono="aviso" :texto="$fila->sin_cerrar.' '.($fila->sin_cerrar === 1 ? 'materia' : 'materias')" />@else — @endif</td>
                            <td><div class="d-flex justify-content-end align-items-center gap-2">
                                @if ($fila->revisado)<x-badge-estado tono="exito" texto="Revisado" icono="bi-check" />@endif
                                <a class="btn btn-sm btn-outline-siges" href="{{ route('cambio-periodo.alumnos.revisar', $fila->grupo) }}" aria-label="Revisar alumnos de {{ $fila->grupo->identificador }}">Revisar</a>
                            </div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$grupos" singular="grupo" plural="grupos" /></div>
    </x-tarjeta>

    <div class="form-acciones">
        <a href="{{ route('cambio-periodo.grupos', ['origen' => $cambio->origen->id, 'destino' => $cambio->destino->id]) }}" class="btn btn-outline-secondary">Volver</a>
        <button type="button" class="btn btn-siges" data-bs-toggle="modal" data-bs-target="#modalConfirmarCambio"><i class="bi bi-check2-circle" aria-hidden="true"></i> Confirmar reinscripción</button>
    </div>
@endsection

@push('modales')
    <x-modal id="modalConfirmarCambio" titulo="Confirmar reinscripción" icono="bi-check2-circle" :accion="route('cambio-periodo.confirmar')" boton="Confirmar reinscripción">
        <ul class="mb-3">
            <li>Se crearán <strong>{{ $porCrear }}</strong> {{ $porCrear === 1 ? 'grupo' : 'grupos' }} y se reutilizarán <strong>{{ $porReutilizar }}</strong> en {{ $cambio->destino->nombre_visible }} ({{ $cambio->destino->clave }}), sin materias ni docentes.</li>
            <li>Se reinscribirán <strong>{{ $marcados }}</strong> {{ $marcados === 1 ? 'alumno' : 'alumnos' }} con fecha de inscripción {{ $cambio->destino->fecha_inicio->format('d/m/Y') }}.</li>
            <li>Omitidos: {{ $omitidos }}. Candidatos a egreso: {{ $totales[CambioDePeriodo::ULTIMO_SEMESTRE] }}.</li>
            <li>Motivo en la bitácora: «{{ $cambio->motivo() }}».</li>
        </ul>
        <p class="texto-tenue small">Todo se hace en una sola operación. Si lo vuelves a correr, no duplica grupos ni inscripciones.</p>
        @if ($sinCerrar > 0)
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="acepto_pendientes" id="aceptoPendientes" value="1" required>
                <label class="form-check-label" for="aceptoPendientes">Entiendo que hay materias sin cerrar y que los adeudos pueden estar incompletos</label>
            </div>
        @endif
    </x-modal>
@endpush
