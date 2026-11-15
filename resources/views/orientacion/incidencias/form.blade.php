@extends('layouts.app')
@php
    $editando = $incidencia->exists;
    $tipoElegido = (string) old('cat_tipo_incidencia_id', $incidencia->cat_tipo_incidencia_id);
@endphp
@section('titulo', $editando ? 'Editar incidencia' : 'Nueva incidencia')
@section('contenido')
    <x-page-header :titulo="$editando ? 'Editar incidencia' : 'Nueva incidencia'"
        :subtitulo="$editando ? 'Solo se edita mientras está abierta. Los cambios quedan en la bitácora.' : 'Registra una incidencia de conducta, suspensión o incapacidad.'"
        :volver="$editando ? [route('incidencias.show', $incidencia), 'Volver a la incidencia'] : [route('incidencias.index'), 'Volver a Incidencias']" />
    <x-errores-formulario />

    @unless ($editando)
        <section class="form-seccion">
            <h2 class="form-seccion-titulo"><span>1</span> Alumno</h2>
            @if ($elegido)
                <p class="mb-1"><strong>{{ $elegido->persona->nombre_completo }}</strong> <span class="mono">{{ $elegido->matricula }}</span> · {{ $elegido->inscripcionActual?->grupo->nombre_completo }}</p>
                <a class="small" href="{{ route('incidencias.create') }}">Elegir otro alumno</a>
            @else
                <p class="texto-tenue">Solo aparecen alumnos de tus grupos con inscripción vigente.</p>
                <x-buscador-alumno :accion="route('incidencias.create')" :buscar="$buscar" />
                @if (filled($buscar))
                    @forelse ($encontrados as $alumno)
                        <div class="d-flex align-items-center justify-content-between gap-2 py-2 border-top">
                            <div><strong>{{ $alumno->persona->nombre_completo }}</strong><small class="d-block mono">{{ $alumno->matricula }} · {{ $alumno->inscripcionActual?->grupo->identificador }}</small></div>
                            <a class="btn btn-sm btn-outline-siges" href="{{ route('incidencias.create', ['alumno' => $alumno->id]) }}">Elegir</a>
                        </div>
                    @empty
                        <p class="texto-tenue mb-0">No se encontraron alumnos de tus grupos.</p>
                    @endforelse
                @endif
                @error('per_alumno_id')<div class="texto-peligro small mt-2">{{ $message }}</div>@enderror
            @endif
        </section>
    @endunless

    @if ($editando || $elegido)
        <form method="POST" action="{{ $editando ? route('incidencias.update', $incidencia) : route('incidencias.store') }}" enctype="multipart/form-data" novalidate>
            @csrf
            @if ($editando) @method('PUT') @else <input type="hidden" name="per_alumno_id" value="{{ $elegido->id }}"> @endif
            <section class="form-seccion">
                <h2 class="form-seccion-titulo"><span>{{ $editando ? 1 : 2 }}</span> Incidencia</h2>
                <div class="row g-3">
                    @if ($editando)
                        <div class="col-md-6">
                            <label for="alumnoBloqueado" class="form-label"><i class="bi bi-lock" aria-hidden="true"></i> Alumno</label>
                            <input id="alumnoBloqueado" class="form-control" value="{{ $incidencia->alumno->persona->nombre_completo }} · {{ $incidencia->inscripcion->grupo->identificador }}" disabled>
                        </div>
                    @endif
                    {{-- data-muestra-condicional: SUSPENSION e INCAPACIDAD muestran la fecha de fin (CSS :has, formularios.css). --}}
                    <x-seleccion name="cat_tipo_incidencia_id" label="Tipo" col="col-md-6" placeholder="Selecciona un tipo" :requerido="true">
                        @foreach ($tipos as $categoria => $deLaCategoria)
                            <optgroup label="{{ $categorias[$categoria] ?? $categoria }}">
                                @foreach ($deLaCategoria as $tipo)
                                    <option value="{{ $tipo->id }}" @selected($tipoElegido === (string) $tipo->id) @if ($tipo->requiereFechaFin()) data-muestra-condicional @endif>{{ $tipo->nombre }} · {{ $tipo->gravedad_nombre }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </x-seleccion>
                    <x-campo name="fecha_inicio" label="Fecha" type="date" :value="$incidencia->fecha_inicio?->format('Y-m-d') ?? today()->format('Y-m-d')" :requerido="true"
                        ayuda="Dentro del periodo activo y no más de 30 días atrás." />
                    <x-campo name="fecha_fin" label="Último día que cubre" type="date" col="col-md-4 campo-condicional" :value="$incidencia->fecha_fin?->format('Y-m-d')" :requerido="true"
                        ayuda="Solo para suspensión e incapacidad." />
                    <div class="col-12">
                        <label for="descripcion" class="form-label">Descripción <span class="requerido" aria-hidden="true">*</span></label>
                        <textarea id="descripcion" name="descripcion" rows="4" minlength="10" maxlength="2000" required class="form-control @error('descripcion') is-invalid @enderror" aria-describedby="descripcion-ayuda">{{ old('descripcion', $incidencia->descripcion) }}</textarea>
                        @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text" id="descripcion-ayuda">Al menos 10 caracteres. No se incluye en la notificación al tutor.</div>
                    </div>
                </div>
            </section>
            <section class="form-seccion">
                <h2 class="form-seccion-titulo"><span>{{ $editando ? 2 : 3 }}</span> Documento</h2>
                <p class="texto-tenue">Receta, incapacidad, acta o citatorio. Obligatorio para las incapacidades.</p>
                @if ($editando && $incidencia->documento)<p>Documento actual: <x-archivo-enlace :archivo="$incidencia->documento" /></p>@endif
                <div class="row g-3">
                    <x-archivo-input name="documento" :label="$editando && $incidencia->documento ? 'Reemplazar documento' : 'Documento'" col="col-md-6"
                        :ayuda="$editando ? 'Opcional. PDF o imagen, máximo 10 MB. El anterior se conserva.' : 'PDF o imagen, máximo 10 MB.'" />
                </div>
            </section>
            @if ($editando)
                <section class="form-seccion">
                    <h2 class="form-seccion-titulo"><span>3</span> Motivo del cambio</h2>
                    <div class="row g-3"><x-campo name="motivo" label="Motivo del cambio" col="col-12" maxlength="255" :requerido="true" ayuda="Queda registrado en la bitácora junto con los cambios." /></div>
                </section>
            @endif
            <div class="form-acciones">
                <a href="{{ $editando ? route('incidencias.show', $incidencia) : route('incidencias.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-siges">{{ $editando ? 'Guardar cambios' : 'Registrar incidencia' }}</button>
            </div>
        </form>
    @endif
@endsection
