@extends('layouts.app')
@php
    $puedeEditar = auth()->user()->can('editar', $alumno);
    $estadoDe = fn ($consentimiento) => $consentimiento ? ($consentimiento->otorgado ? 'OTORGADO' : 'REVOCADO') : 'SIN_CONSENTIMIENTO';
@endphp
@section('titulo', 'Consentimientos')
@section('contenido')
    <x-page-header :titulo="'Consentimientos · '.$alumno->persona->nombre_completo" subtitulo="Otorgar o revocar es un registro nuevo con su documento firmado; el historial no se edita ni se borra."
        :volver="[route('alumnos.show', $alumno), 'Volver a la ficha']" />
    <div class="row g-4">
        @if ($puedeEditar)
            <div class="col-lg-4">
                <x-tarjeta titulo="Registrar consentimiento" icono="bi-file-earmark-plus">
                    <x-errores-formulario />
                    <form method="POST" action="{{ route('alumnos.consentimientos.store', $alumno) }}" enctype="multipart/form-data" novalidate>
                        @csrf
                        <div class="row g-3">
                            <x-seleccion name="per_tutor_alumno_id" label="Tutor" col="col-12" :requerido="true" placeholder="Selecciona un tutor"
                                :opciones="$alumno->tutores->mapWithKeys(fn ($tutor) => [$tutor->id => $tutor->persona->nombre_completo.' · '.$tutor->parentesco_nombre])" />
                            <x-seleccion name="tipo" label="Tipo" col="col-12" :opciones="$tipos" placeholder="Selecciona un tipo" :requerido="true" />
                            <fieldset class="col-12">
                                <legend class="form-label fs-6">Sentido <span class="requerido" aria-hidden="true">*</span></legend>
                                @foreach (['1' => 'Otorga', '0' => 'Revoca'] as $valor => $etiqueta)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input @error('otorgado') is-invalid @enderror" type="radio" name="otorgado" id="otorgado{{ $valor }}" value="{{ $valor }}" @checked(old('otorgado') === (string) $valor) required>
                                        <label class="form-check-label" for="otorgado{{ $valor }}">{{ $etiqueta }}</label>
                                    </div>
                                @endforeach
                                @error('otorgado')<div class="texto-peligro small">{{ $message }}</div>@enderror
                            </fieldset>
                            <x-campo name="fecha" label="Fecha de firma" type="date" col="col-12" :value="now()->format('Y-m-d')" :max="now()->format('Y-m-d')" :requerido="true" ayuda="No puede ser anterior a la del consentimiento vigente del mismo tipo." />
                            <x-archivo-input name="documento" label="Documento firmado" col="col-12" :requerido="true" />
                        </div>
                        <div class="form-acciones mt-4 mb-0">
                            <a class="btn btn-outline-secondary" href="{{ route('alumnos.show', $alumno) }}">Cancelar</a>
                            <button type="submit" class="btn btn-siges">Registrar</button>
                        </div>
                    </form>
                </x-tarjeta>
            </div>
        @endif
        <div class="{{ $puedeEditar ? 'col-lg-8' : 'col-12' }}">
            <x-tarjeta titulo="Vigentes" icono="bi-check2-square" sin-relleno>
                <div class="table-responsive">
                    <table class="table tabla-siges align-middle mb-0">
                        <thead><tr><th scope="col">Tutor</th>@foreach ($tipos as $nombre)<th scope="col">{{ $nombre }}</th>@endforeach</tr></thead>
                        <tbody>
                        @foreach ($alumno->tutores as $tutor)
                            <tr>
                                <th scope="row"><strong>{{ $tutor->persona->nombre_completo }}</strong><small class="d-block texto-tenue fw-normal">{{ $tutor->parentesco_nombre }}</small></th>
                                @foreach ($tipos as $tipo => $nombre)
                                    @php $vigente = $matriz[$tutor->id][$tipo] ?? null; @endphp
                                    <td>
                                        <x-badge-estado :estado="$estadoDe($vigente)" />
                                        @if ($vigente)
                                            <small class="d-block texto-tenue mt-1">{{ $vigente->fecha->format('d/m/Y') }}</small>
                                            <small><x-archivo-enlace :archivo="$vigente->archivo" /></small>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="tabla-pie"><span>{{ $alumno->tutores->count() }} {{ $alumno->tutores->count() === 1 ? 'tutor' : 'tutores' }} · {{ count($tipos) }} tipos de consentimiento</span></div>
            </x-tarjeta>
        </div>
        <div class="col-12">
            <x-tarjeta titulo="Historial" icono="bi-clock-history" sin-relleno>
                @if ($historial->isEmpty())
                    <x-estado-vacio icono="bi-file-earmark-check" texto="Aún no hay consentimientos registrados." />
                @else
                    <div class="table-responsive">
                        <table class="table tabla-siges align-middle mb-0">
                            <thead><tr><th scope="col">Fecha de firma</th><th scope="col">Tutor</th><th scope="col">Tipo</th><th scope="col">Sentido</th><th scope="col">Documento</th><th scope="col">Registrado</th></tr></thead>
                            <tbody>
                            @foreach ($historial as $consentimiento)
                                <tr>
                                    <td>{{ $consentimiento->fecha->format('d/m/Y') }}</td>
                                    <td>{{ $consentimiento->tutor->persona->nombre_completo }}</td>
                                    <td>{{ $consentimiento->tipo_nombre }}</td>
                                    <td><x-badge-estado :estado="$estadoDe($consentimiento)" /></td>
                                    <td><x-archivo-enlace :archivo="$consentimiento->archivo" /></td>
                                    <td>{{ $consentimiento->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                <div class="tabla-pie"><span>{{ $historial->count() }} {{ $historial->count() === 1 ? 'registro' : 'registros' }}</span></div>
            </x-tarjeta>
        </div>
    </div>
@endsection
