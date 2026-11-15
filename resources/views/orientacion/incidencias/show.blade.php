@extends('layouts.app')
@php
    $alumno = $incidencia->alumno;
    $tipo = $incidencia->tipo;
@endphp
@section('titulo', 'Incidencia')
@section('contenido')
    <x-page-header :titulo="$tipo->nombre.' · '.$alumno->persona->nombre_completo" :volver="[route('incidencias.index'), 'Volver a Incidencias']">
        <x-slot:subtitulo>{{ $tipo->categoria_nombre }} · <x-badge-estado :estado="$tipo->nivel_gravedad" /> · <x-badge-estado :estado="$incidencia->estado" /></x-slot:subtitulo>
        <x-slot:acciones>
            @can('editar', $incidencia)<a href="{{ route('incidencias.edit', $incidencia) }}" class="btn btn-outline-siges"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a>@endcan
            @can('seguimiento', $incidencia)
                <form method="POST" action="{{ route('incidencias.seguimiento', $incidencia) }}">@csrf @method('PATCH')<button type="submit" class="btn btn-outline-siges"><i class="bi bi-eye" aria-hidden="true"></i> Tomar seguimiento</button></form>
            @endcan
            @can('cerrar', $incidencia)<button type="button" class="btn btn-siges" data-bs-toggle="modal" data-bs-target="#modalCerrar"><i class="bi bi-check2-circle" aria-hidden="true"></i> Cerrar</button>@endcan
        </x-slot:acciones>
    </x-page-header>
    @error('resolucion')<x-errores-formulario />@enderror

    <div class="row g-4">
        <div class="col-lg-8">
            <x-tarjeta titulo="Datos" icono="bi-exclamation-triangle" class="h-100">
                <dl class="dato-lista">
                    <dt>Alumno</dt>
                    <dd>@can('ver', $alumno)<a href="{{ route('alumnos.show', $alumno) }}">{{ $alumno->persona->nombre_completo }}</a>@else{{ $alumno->persona->nombre_completo }}@endcan
                        <small class="d-block mono">{{ $alumno->matricula }} · {{ $incidencia->inscripcion->grupo->nombre_completo }}</small></dd>
                    <dt>Fecha</dt><dd>{{ $incidencia->fecha_inicio->format('d/m/Y') }}@if ($incidencia->fecha_fin) al {{ $incidencia->fecha_fin->format('d/m/Y') }}@endif</dd>
                    <dt>Descripción</dt><dd>{{ $incidencia->descripcion }}</dd>
                    <dt>Notifica al tutor</dt><dd>{{ $tipo->notifica_tutor ? 'Sí' : 'No' }}</dd>
                    <dt>Justifica falta</dt><dd>{{ $tipo->justifica_falta ? 'Sí: sus faltas y retardos de esos días quedan Justificados' : 'No' }}</dd>
                    <dt>Documento</dt><dd><x-archivo-enlace :archivo="$incidencia->documento" /></dd>
                    <dt>Reportó</dt><dd>{{ $incidencia->reportadoPor?->name }}</dd>
                    @if ($incidencia->estado === \App\Models\TrxIncidencia::CERRADA)
                        <dt>Resolución</dt><dd>{{ $incidencia->resolucion }}</dd>
                    @endif
                </dl>
            </x-tarjeta>
        </div>
        <div class="col-lg-4">
            <x-tarjeta titulo="Línea de tiempo" icono="bi-signpost-split" class="h-100">
                <ol class="linea-tiempo">
                    @foreach ($lineaDeTiempo as $paso)
                        <li @class(['hecho' => $paso['hecho']])>
                            <span class="paso-icono"><i class="bi {{ $paso['icono'] }}" aria-hidden="true"></i></span>
                            <div class="paso-texto">
                                <strong>{{ $paso['titulo'] }}</strong>
                                @if ($paso['hecho'] && $paso['fecha'])<small class="d-block texto-tenue">{{ $paso['fecha']->format('d/m/Y') }}@if ($paso['quien']) · {{ $paso['quien'] }}@endif</small>
                                @elseif (! $paso['hecho'])<small class="d-block texto-tenue">Pendiente</small>@endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </x-tarjeta>
        </div>
    </div>
@endsection

@push('modales')
    @can('cerrar', $incidencia)
        <x-modal id="modalCerrar" titulo="Cerrar incidencia" icono="bi-check2-circle" metodo="PATCH" :accion="route('incidencias.cerrar', $incidencia)" boton="Cerrar incidencia">
            <p>Una incidencia cerrada ya no se puede modificar.</p>
            <label for="resolucion" class="form-label">Resolución <span class="requerido" aria-hidden="true">*</span></label>
            <textarea id="resolucion" name="resolucion" rows="4" minlength="10" maxlength="2000" required class="form-control @error('resolucion') is-invalid @enderror" aria-describedby="resolucion-ayuda">{{ old('resolucion') }}</textarea>
            <div class="form-text" id="resolucion-ayuda">Al menos 10 caracteres: qué se acordó o qué medida se tomó.</div>
        </x-modal>
    @endcan
@endpush
