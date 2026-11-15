@extends('layouts.app')
@php
    $motivoElegido = old('motivo', $motivo);
    // Aviso de caso abierto con el mismo motivo (TrxSeguimientoAlumno::abrir).
    $duplicado = $errors->has('confirmar_duplicado');
@endphp
@section('titulo', 'Abrir caso de seguimiento')
@section('contenido')
    <x-page-header titulo="Abrir caso de seguimiento" subtitulo="El caso queda en la inscripción vigente del alumno. La descripción es confidencial y no se copia a la bitácora."
        :volver="[route('seguimiento.index'), 'Volver a Seguimiento']" />
    @unless ($duplicado)<x-errores-formulario />@endunless

    <section class="form-seccion">
        <h2 class="form-seccion-titulo"><span>1</span> Alumno</h2>
        @if ($elegido)
            <p class="mb-1"><strong>{{ $elegido->persona->nombre_completo }}</strong> <span class="mono">{{ $elegido->matricula }}</span> · {{ $elegido->inscripcionActual?->grupo->nombre_completo }}</p>
            <a class="small" href="{{ route('seguimiento.create', array_filter(['motivo' => $motivoElegido])) }}">Elegir otro alumno</a>
            @if ($abiertos->isNotEmpty())
                <div class="mt-3">
                    <p class="mb-1 texto-tenue">Casos abiertos de este alumno:</p>
                    <ul class="mb-0">
                        @foreach ($abiertos as $abierto)
                            <li><a href="{{ route('seguimiento.show', $abierto) }}">#{{ $abierto->id }} · {{ $abierto->motivo_nombre }}</a> · {{ $abierto->estado_nombre }} · {{ $abierto->inscripcion->grupo->periodo->nombre_visible }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @else
            <p class="texto-tenue">Solo aparecen alumnos de los grupos que acompañas (orientador o tutor) con inscripción vigente.</p>
            <x-buscador-alumno :accion="route('seguimiento.create')" :buscar="$buscar" />
            @if ($motivo)<p class="small texto-tenue mt-2">Motivo sugerido: {{ $motivos[$motivo] }}.</p>@endif
            @if (filled($buscar))
                @forelse ($encontrados as $alumno)
                    <div class="d-flex align-items-center justify-content-between gap-2 py-2 border-top">
                        <div><strong>{{ $alumno->persona->nombre_completo }}</strong><small class="d-block mono">{{ $alumno->matricula }} · {{ $alumno->inscripcionActual?->grupo->identificador }}</small></div>
                        <a class="btn btn-sm btn-outline-siges" href="{{ route('seguimiento.create', array_filter(['alumno' => $alumno->id, 'motivo' => $motivo])) }}">Elegir</a>
                    </div>
                @empty
                    <p class="texto-tenue mb-0">No se encontraron alumnos de tus grupos.</p>
                @endforelse
            @endif
            @error('per_alumno_id')<div class="texto-peligro small mt-2">{{ $message }}</div>@enderror
        @endif
    </section>

    @if ($elegido)
        <form method="POST" action="{{ route('seguimiento.store') }}" novalidate>
            @csrf
            <input type="hidden" name="per_alumno_id" value="{{ $elegido->id }}">
            <section class="form-seccion">
                <h2 class="form-seccion-titulo"><span>2</span> Caso</h2>
                <div class="row g-3">
                    <x-seleccion name="motivo" label="Motivo" col="col-md-6" :opciones="$motivos" :valor="$motivoElegido" placeholder="Selecciona un motivo" :requerido="true" />
                    <x-seleccion name="riesgo_abandono" label="Riesgo de abandono" col="col-md-6" :opciones="['0' => 'No', '1' => 'Sí']" :valor="old('riesgo_abandono', '0')" :requerido="true"
                        ayuda="Se puede cambiar después, con motivo, mientras el caso no esté cerrado." />
                    <div class="col-12">
                        <label for="descripcion" class="form-label">Descripción <span class="requerido" aria-hidden="true">*</span></label>
                        <textarea id="descripcion" name="descripcion" rows="5" minlength="10" maxlength="4000" required class="form-control @error('descripcion') is-invalid @enderror" aria-describedby="descripcion-ayuda">{{ old('descripcion') }}</textarea>
                        @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text" id="descripcion-ayuda">Al menos 10 caracteres: qué se observó y por qué se abre el caso.</div>
                    </div>
                    @if ($duplicado)
                        <div class="col-12">
                            <x-aviso tipo="aviso">
                                {{ $errors->first('confirmar_duplicado') }}
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="confirmar_duplicado" value="1" id="confirmar_duplicado">
                                    <label class="form-check-label" for="confirmar_duplicado">Abrir otro caso de todos modos</label>
                                </div>
                            </x-aviso>
                        </div>
                    @endif
                </div>
            </section>
            <div class="form-acciones">
                <a href="{{ route('seguimiento.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-siges">Abrir caso</button>
            </div>
        </form>
    @endif
@endsection
