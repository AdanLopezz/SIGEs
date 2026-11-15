@extends('layouts.app')
@php
    $alumno = $caso->inscripcion->alumno;
    $periodo = $caso->inscripcion->grupo->periodo;
    $iconos = ['ENTREVISTA_ALUMNO' => 'bi-person', 'REUNION_PADRE' => 'bi-people', 'TUTORIA' => 'bi-mortarboard', 'CANALIZACION' => 'bi-signpost',
        'VISITA_DOMICILIARIA' => 'bi-house', 'LLAMADA' => 'bi-telephone'];
    $puedeOperar = auth()->user()->can('operar', $caso);
    $puedeCerrar = auth()->user()->can('cerrar', $caso);
    $puedeContinuar = auth()->user()->can('continuar', $caso);
    $puedeReabrir = auth()->user()->can('reabrir', $caso);
    $errReabrir = $errors->getBag('reabrir');
    $errRiesgo = $errors->getBag('riesgo');
@endphp
@section('titulo', 'Caso de seguimiento #'.$caso->id)
@section('contenido')
    <x-page-header :titulo="'Caso #'.$caso->id.' · '.$alumno->persona->nombre_completo" :volver="[route('seguimiento.index'), 'Volver a Seguimiento']">
        <x-slot:subtitulo>{{ $caso->motivo_nombre }} · <x-badge-estado :estado="$caso->estado" />@if ($caso->riesgo_abandono) <x-badge-estado estado="RIESGO_ABANDONO" />@endif</x-slot:subtitulo>
        <x-slot:acciones>
            @if ($puedeContinuar)<button type="button" class="btn btn-siges" data-bs-toggle="modal" data-bs-target="#modalContinuar"><i class="bi bi-arrow-right-circle" aria-hidden="true"></i> Continuar en el periodo actual</button>@endif
            @if ($puedeCerrar)<button type="button" class="btn {{ $puedeContinuar ? 'btn-outline-siges' : 'btn-siges' }}" data-bs-toggle="modal" data-bs-target="#modalCerrar"><i class="bi bi-check2-circle" aria-hidden="true"></i> Cerrar caso</button>@endif
            @if ($puedeReabrir)<button type="button" class="btn btn-outline-siges" data-bs-toggle="modal" data-bs-target="#modalReabrir"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reabrir</button>@endif
        </x-slot:acciones>
    </x-page-header>

    @if (! $caso->estaCerrado() && $caso->esDePeriodoPasado())
        <x-aviso tipo="aviso">Este caso es del periodo {{ $periodo->nombre_visible }}, que ya pasó: es de solo lectura. Ciérralo o continúalo en el periodo actual.</x-aviso>
    @elseif (! $caso->esDelPeriodoActivo())
        <x-aviso tipo="info">Caso del periodo {{ $periodo->nombre_visible }}: solo consulta.</x-aviso>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <x-tarjeta titulo="Datos del caso" icono="bi-person-heart">
                <x-slot:acciones>
                    @if ($puedeOperar)<button type="button" class="btn btn-sm btn-outline-siges" data-bs-toggle="modal" data-bs-target="#modalRiesgo">Cambiar riesgo</button>@endif
                </x-slot:acciones>
                <dl class="dato-lista">
                    <dt>Alumno</dt>
                    <dd>@can('ver', $alumno)<a href="{{ route('alumnos.show', $alumno) }}">{{ $alumno->persona->nombre_completo }}</a>@else{{ $alumno->persona->nombre_completo }}@endcan
                        <small class="d-block mono">{{ $alumno->matricula }} · {{ $caso->inscripcion->grupo->nombre_completo }}</small></dd>
                    <dt>Periodo</dt><dd>{{ $periodo->nombre_visible }} <span class="mono small">{{ $periodo->clave }}</span></dd>
                    <dt>Motivo</dt><dd>{{ $caso->motivo_nombre }}</dd>
                    <dt>Riesgo de abandono</dt><dd>{{ $caso->riesgo_abandono ? 'Sí' : 'No' }}</dd>
                    <dt>Apertura</dt><dd>{{ $caso->fecha_apertura->format('d/m/Y') }} · {{ $caso->abiertoPor?->name }}</dd>
                    <dt>Descripción</dt><dd class="seguimiento-texto">{{ $caso->descripcion }}</dd>
                </dl>
            </x-tarjeta>
        </div>
        <div class="col-lg-7">
            @if ($puedeOperar)
                <x-tarjeta titulo="Agregar nota" icono="bi-journal-plus" class="mb-4">
                    <form method="POST" action="{{ route('seguimiento.nota', $caso) }}" novalidate>
                        @csrf
                        @include('orientacion.seguimiento._nota-campos', ['bolsa' => 'default', 'prefijo' => 'nota', 'conFecha' => true, 'conMotivo' => false, 'minimo' => $caso->fecha_apertura->format('Y-m-d')])
                        <div class="form-acciones">
                            <button type="submit" class="btn btn-siges">Agregar nota</button>
                        </div>
                    </form>
                    <p class="small texto-tenue mb-0 mt-2">Las notas no se editan ni se borran. Su texto no se copia a la bitácora.</p>
                </x-tarjeta>
            @endif
            <x-tarjeta titulo="Notas" icono="bi-signpost-split">
                @if ($caso->notas->isEmpty())
                    <x-estado-vacio icono="bi-journal" texto="Todavía no hay notas." />
                @else
                    <ol class="linea-tiempo">
                        @foreach ($caso->notas as $nota)
                            <li class="hecho">
                                <span class="paso-icono"><i class="bi {{ $iconos[$nota->tipo_atencion] ?? 'bi-journal' }}" aria-hidden="true"></i></span>
                                <div class="paso-texto">
                                    <strong>{{ $nota->tipo_nombre }}</strong>
                                    <small class="d-block texto-tenue">{{ $nota->fecha->format('d/m/Y') }} · {{ $nota->registradoPor?->name }}</small>
                                    <p class="mb-1 mt-2 seguimiento-texto">{{ $nota->descripcion }}</p>
                                    <p class="mb-0 seguimiento-texto"><strong>Acuerdos:</strong> {{ $nota->acuerdos }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-tarjeta>
        </div>
    </div>
@endsection

@push('modales')
    @if ($puedeCerrar)
        <x-modal id="modalCerrar" titulo="Cerrar caso" icono="bi-check2-circle" metodo="PATCH" :accion="route('seguimiento.cerrar', $caso)" boton="Cerrar caso"
            :data-abrir-al-cargar="$errors->getBag('cerrar')->any() ? true : null">
            <p>La nota final queda con la fecha de hoy. Un caso cerrado se puede reabrir con motivo{{ $caso->esDePeriodoPasado() ? ' solo si es del periodo activo' : '' }}.</p>
            @include('orientacion.seguimiento._nota-campos', ['bolsa' => 'cerrar', 'prefijo' => 'cerrar', 'conFecha' => false, 'conMotivo' => true])
        </x-modal>
    @endif
    @if ($puedeContinuar)
        <x-modal id="modalContinuar" titulo="Continuar en el periodo actual" icono="bi-arrow-right-circle" :accion="route('seguimiento.continuar', $caso)" boton="Continuar"
            :data-abrir-al-cargar="$errors->getBag('continuar')->any() ? true : null">
            <p>Se abre un caso nuevo en el periodo activo con el mismo motivo y riesgo, con la referencia a este caso, y este se cierra con la nota final.</p>
            @include('orientacion.seguimiento._nota-campos', ['bolsa' => 'continuar', 'prefijo' => 'continuar', 'conFecha' => false, 'conMotivo' => true])
        </x-modal>
    @endif
    @if ($puedeReabrir)
        <x-modal id="modalReabrir" titulo="Reabrir caso" icono="bi-arrow-counterclockwise" metodo="PATCH" :accion="route('seguimiento.reabrir', $caso)" boton="Reabrir"
            :data-abrir-al-cargar="$errReabrir->any() ? true : null">
            <p>El caso vuelve a "En seguimiento" y puede recibir notas.</p>
            <label for="reabrirMotivo" class="form-label">Motivo (bitácora) <span class="requerido" aria-hidden="true">*</span></label>
            <input type="text" id="reabrirMotivo" name="motivo" maxlength="255" required value="{{ $errReabrir->any() ? old('motivo') : '' }}" class="form-control @if ($errReabrir->has('motivo')) is-invalid @endif">
            @if ($errReabrir->has('motivo'))<div class="invalid-feedback">{{ $errReabrir->first('motivo') }}</div>@endif
        </x-modal>
    @endif
    @if ($puedeOperar)
        <x-modal id="modalRiesgo" titulo="Riesgo de abandono" icono="bi-exclamation-octagon" metodo="PATCH" :accion="route('seguimiento.riesgo', $caso)" boton="Guardar"
            :data-abrir-al-cargar="$errRiesgo->any() ? true : null">
            <input type="hidden" name="riesgo_abandono" value="{{ $caso->riesgo_abandono ? 0 : 1 }}">
            <p>{{ $caso->riesgo_abandono ? 'Se quitará la marca de riesgo de abandono.' : 'Se marcará el caso en riesgo de abandono.' }}</p>
            <label for="riesgoMotivo" class="form-label">Motivo (bitácora) <span class="requerido" aria-hidden="true">*</span></label>
            <input type="text" id="riesgoMotivo" name="motivo" maxlength="255" required value="{{ $errRiesgo->any() ? old('motivo') : '' }}" class="form-control @if ($errRiesgo->any()) is-invalid @endif">
            @if ($errRiesgo->any())<div class="invalid-feedback">{{ $errRiesgo->first() }}</div>@endif
        </x-modal>
    @endif
@endpush
