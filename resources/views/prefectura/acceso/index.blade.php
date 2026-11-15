@extends('layouts.app')
@section('titulo', 'Control de acceso')
@section('contenido')
    <x-page-header titulo="Control de acceso" subtitulo="Lee la credencial con el lector o con la cámara. Cada lectura alterna entrada y salida." />

    {{-- Escala normal; solo el campo (form-control-lg) y el resultado son más grandes. Los data-* los usa control-acceso.js. --}}
    <x-tarjeta titulo="Lectura de credencial" icono="bi-qr-code-scan" class="mb-4">
        <form method="POST" action="{{ route('acceso.registrar') }}" data-control-acceso autocomplete="off">
            @csrf
            <label for="codigo" class="form-label">Código de la credencial <span class="requerido" aria-hidden="true">*</span></label>
            <div class="acceso-lector">
                <div class="input-group">
                    <input id="codigo" name="codigo" class="form-control" maxlength="500" required autofocus placeholder="Escanea la credencial…" aria-describedby="codigoAyuda">
                    <button type="submit" class="btn btn-siges"><i class="bi bi-check2-circle" aria-hidden="true"></i> Registrar</button>
                </div>
                <button type="button" class="btn btn-outline-siges" data-usar-camara><i class="bi bi-camera-video" aria-hidden="true"></i> <span>Usar cámara</span></button>
            </div>
            <div class="form-text" id="codigoAyuda">El lector escribe la dirección de la credencial y Enter. También puedes pegar solo el código.</div>
            <x-aviso tipo="aviso" class="mt-3 mb-0" data-aviso-camara hidden>
                <span data-aviso-texto>La cámara solo funciona si entras por <strong>HTTPS</strong> o desde <strong>localhost</strong>. Con una dirección como http://192.168.…, usa el lector USB o Bluetooth.</span>
            </x-aviso>
            <div class="acceso-camara" data-camara><video playsinline muted aria-label="Vista de la cámara"></video></div>
        </form>
    </x-tarjeta>

    <div class="mb-4" data-resultado>@include('prefectura.acceso._resultado', ['resultado' => $resultado])</div>

    <div class="row g-4">
        <div class="col-lg-8">
            <x-tarjeta titulo="Últimos registros de hoy" icono="bi-clock-history" sin-relleno>
                <div data-ultimos>@include('prefectura.acceso._ultimos', ['ultimos' => $ultimos])</div>
            </x-tarjeta>
        </div>
        <div class="col-lg-4">
            <x-tarjeta titulo="Registro manual" icono="bi-keyboard">
                <p class="texto-tenue small">Si la credencial no se puede leer. Requiere que el alumno tenga una credencial activa.</p>
                <x-buscador-alumno :accion="route('acceso.index')" :buscar="$buscar" />
                @if (filled($buscar))
                    @forelse ($encontrados as $alumno)
                        <div class="d-flex align-items-center justify-content-between gap-2 py-2 border-top">
                            <div><strong>{{ $alumno->persona->nombre_completo }}</strong><small class="d-block mono">{{ $alumno->matricula }} · {{ $alumno->inscripcionActual?->grupo->identificador ?? 'Sin grupo' }}</small></div>
                            @if ($alumno->credencialActiva)
                                <form method="POST" action="{{ route('acceso.manual') }}">@csrf<input type="hidden" name="per_alumno_id" value="{{ $alumno->id }}"><button class="btn btn-sm btn-outline-siges" type="submit">Registrar</button></form>
                            @else
                                <small class="texto-peligro text-end">Sin credencial activa.<br>Emítela en Control Escolar.</small>
                            @endif
                        </div>
                    @empty
                        <p class="texto-tenue mb-0">No se encontraron alumnos.</p>
                    @endforelse
                @endif
            </x-tarjeta>
        </div>
    </div>
@endsection
