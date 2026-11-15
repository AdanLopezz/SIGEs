@extends('layouts.app')
@php
    $propuesta = $cambio->propuesta();
    $ultimos = $cambio->gruposUltimoSemestre();
    $parametros = ['origen' => $cambio->origen->id, 'destino' => $cambio->destino->id];
@endphp
@section('titulo', 'Cambio de periodo · Grupos')
@section('contenido')
    <x-page-header titulo="Cambio de periodo" :volver="[route('cambio-periodo.index', $parametros), 'Volver a Periodos']">
        <x-slot:subtitulo>De <strong>{{ $cambio->origen->nombre_visible }}</strong> ({{ $cambio->origen->clave }}) a <strong>{{ $cambio->destino->nombre_visible }}</strong> ({{ $cambio->destino->clave }}).</x-slot:subtitulo>
    </x-page-header>
    @include('control-escolar.cambio-periodo._pasos', ['paso' => 2])
    <x-errores-formulario />

    <x-aviso tipo="info">Las materias y los docentes <strong>no se copian</strong>: asígnalos después en Académico → Grupos, con el periodo destino.</x-aviso>
    @if ($ultimos->isNotEmpty())
        <x-aviso tipo="info">No se copian los grupos de último semestre ({{ $ultimos->pluck('identificador')->implode(', ') }}): sus alumnos quedan como candidatos a egreso.</x-aviso>
    @endif

    <x-tarjeta titulo="Orientador y tutor" icono="bi-person-badge">
        <form method="GET" action="{{ route('cambio-periodo.grupos') }}" class="d-flex flex-wrap align-items-center gap-3">
            <input type="hidden" name="origen" value="{{ $cambio->origen->id }}">
            <input type="hidden" name="destino" value="{{ $cambio->destino->id }}">
            <input type="hidden" name="conservar" value="0">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" name="conservar" id="conservar" value="1" @checked($cambio->conservaResponsables())>
                <label class="form-check-label" for="conservar">Conservar el orientador y el tutor de cada grupo (si siguen activos con su rol)</label>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-siges">Aplicar</button>
        </form>
        <p class="texto-tenue small mt-2 mb-0">Aplicar vuelve a proponer los grupos: se pierden los identificadores y responsables que hayas cambiado en esta pantalla.</p>
    </x-tarjeta>

    <x-tarjeta sin-relleno etiqueta="Grupos propuestos en el destino">
        @if ($propuesta->isEmpty())
            <x-estado-vacio icono="bi-people" texto="El periodo origen no tiene grupos que pasen al siguiente semestre." />
        @else
            <form method="POST" action="{{ route('cambio-periodo.grupos.guardar') }}" id="formGrupos">
                @csrf
                <div class="table-responsive">
                    <table class="table tabla-siges align-middle mb-0">
                        <thead><tr><th scope="col">Grupo origen</th><th scope="col">Grupo destino</th><th scope="col">Orientador</th><th scope="col">Tutor</th><th scope="col">Propuesta</th></tr></thead>
                        <tbody>
                        @foreach ($propuesta as $fila)
                            @php $id = $fila->origen->id; @endphp
                            <tr>
                                <td><strong>{{ $fila->origen->identificador }}</strong><small class="d-block texto-tenue">{{ $fila->origen->semestre }}.º · {{ $fila->carrera->nombre }} · {{ $fila->turno->nombre }}</small></td>
                                <td>
                                    <input type="text" name="grupos[{{ $id }}][identificador]" value="{{ old("grupos.{$id}.identificador", $fila->identificador) }}" maxlength="20" required
                                        class="form-control form-control-sm mono" aria-label="Identificador en el destino para {{ $fila->origen->identificador }}" autocapitalize="characters">
                                    <small class="d-block texto-tenue">{{ $fila->semestre }}.º semestre</small>
                                </td>
                                @if ($fila->estado === \App\Support\CambioDePeriodo::REUTILIZAR)
                                    <td>{{ $fila->existente->orientador?->nombre }}</td>
                                    <td>{{ $fila->existente->tutor?->nombre }}<small class="d-block texto-tenue">Conserva los del grupo existente.</small></td>
                                @else
                                    <td>
                                        <select name="grupos[{{ $id }}][orientador_id]" class="form-select form-select-sm" aria-label="Orientador de {{ $fila->identificador }}">
                                            <option value="">Elige…</option>
                                            @foreach ($orientadores as $valor => $nombre)<option value="{{ $valor }}" @selected((int) $fila->orientador_id === (int) $valor)>{{ $nombre }}</option>@endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="grupos[{{ $id }}][tutor_id]" class="form-select form-select-sm" aria-label="Tutor de {{ $fila->identificador }}">
                                            <option value="">Elige…</option>
                                            @foreach ($tutores as $valor => $nombre)<option value="{{ $valor }}" @selected((int) $fila->tutor_id === (int) $valor)>{{ $nombre }}</option>@endforeach
                                        </select>
                                    </td>
                                @endif
                                <td>
                                    <x-badge-estado :estado="$fila->estado" />
                                    @if ($fila->problema)<small class="d-block texto-peligro">{{ $fila->problema }}</small>
                                    @elseif ($fila->estado === \App\Support\CambioDePeriodo::REUTILIZAR)<small class="d-block texto-tenue">Ya existe en el destino: no se duplica.</small>@endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="tabla-pie"><span>{{ $propuesta->count() }} {{ $propuesta->count() === 1 ? 'grupo' : 'grupos' }} · {{ $propuesta->where('estado', 'CREAR')->count() }} por crear · {{ $propuesta->where('estado', 'REUTILIZAR')->count() }} por reutilizar</span></div>
            </form>
        @endif
    </x-tarjeta>

    <div class="form-acciones">
        <a href="{{ route('cambio-periodo.index', $parametros) }}" class="btn btn-outline-secondary">Volver</a>
        @if ($propuesta->isNotEmpty())
            <button type="submit" form="formGrupos" class="btn btn-siges">Guardar y continuar <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        @else
            {{-- Sin grupos que copiar: igual se revisa a los alumnos (candidatos a egreso). --}}
            <form method="POST" action="{{ route('cambio-periodo.grupos.guardar') }}">@csrf<input type="hidden" name="grupos[0][identificador]" value="X"><button type="submit" class="btn btn-siges">Continuar <i class="bi bi-arrow-right" aria-hidden="true"></i></button></form>
        @endif
    </div>
@endsection
