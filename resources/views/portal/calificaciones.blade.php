@extends('layouts.app')
@section('titulo', 'Calificaciones')
@section('contenido')
<div class="portal">
    <x-page-header titulo="Calificaciones" :subtitulo="'Aprueba con '.$minima.' o más. Los parciales aparecen cuando el docente cierra la evaluación.'" />
    @include('portal._encabezado')

    {{-- Misma regla que la ficha: solo parciales de evaluaciones cerradas; las abiertas, "En captura". --}}
    <x-tarjeta :titulo="$periodo ? $periodo->nombre_visible.' ('.$periodo->clave.')' : 'Periodo'" icono="bi-clipboard-data" class="mb-4">
        @if (! $boleta)
            <x-estado-vacio icono="bi-clipboard-data" :texto="'Sin inscripción en '.($periodo?->nombre_visible ?? 'el periodo').'.'" />
        @elseif ($boleta['materias']->isEmpty())
            <x-estado-vacio icono="bi-clipboard-data" texto="El grupo aún no tiene materias asignadas." />
        @else
            <ul class="portal-lista">
                @foreach ($boleta['materias'] as $materia)
                    @php $resultado = $boleta['resultados']->get($materia->id); @endphp
                    <li class="portal-item">
                        <div class="portal-item-encabezado">
                            <strong>{{ $materia->asignatura->nombre }}</strong>
                            <div class="portal-badges">
                                @if ($resultado)<x-badge-estado :estado="$resultado->oportunidad" />@endif
                                <x-badge-estado :estado="$resultado?->estado ?? 'SIN_RESULTADO'" />
                            </div>
                        </div>
                        <div class="portal-parciales">
                            @foreach ($materia->evaluaciones as $evaluacion)
                                <div class="portal-parcial">
                                    <small>{{ $evaluacion->tipo->nombre }}</small>
                                    @if ($evaluacion->cerrada)
                                        <strong class="mono">{{ $boleta['calificaciones']->get($evaluacion->id)?->valor ?? '—' }}</strong>
                                    @else
                                        <x-badge-estado estado="EN_CAPTURA" />
                                    @endif
                                </div>
                            @endforeach
                            <div class="portal-parcial definitiva"><small>Definitiva</small><strong class="mono">{{ $resultado?->calificacion ?? '—' }}</strong></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-tarjeta>

    {{-- Recursamientos cursados en el periodo y los de materias reprobadas en él (aunque se cursen después). --}}
    @if ($recursamientos->isNotEmpty())
        <x-tarjeta titulo="Recursamientos" icono="bi-arrow-repeat" class="mb-4">
            <ul class="portal-lista">
                @foreach ($recursamientos as $recursamiento)
                    @php $resultadoRecursamiento = $recursamiento->resultadoDestino(); @endphp
                    <li class="portal-item">
                        <div class="portal-item-encabezado">
                            <strong>{{ $recursamiento->materia->asignatura->nombre }}</strong>
                            <x-badge-estado :estado="$resultadoRecursamiento?->estado ?? 'EN_RECURSAMIENTO'" />
                        </div>
                        <dl class="dato-lista">
                            <dt>Reprobada en</dt><dd>{{ $recursamiento->origen->materia->grupo->periodo->nombre_visible }} · {{ $recursamiento->origen->materia->grupo->identificador }}</dd>
                            <dt>Recursada en</dt><dd>{{ $recursamiento->materia->grupo->periodo->nombre_visible }} · {{ $recursamiento->materia->grupo->identificador }} · {{ $recursamiento->materia->docente->nombre }}</dd>
                            <dt>Desde</dt><dd>{{ $recursamiento->fecha_inicio->format('d/m/Y') }}</dd>
                            <dt>Definitiva</dt><dd class="mono">{{ $resultadoRecursamiento?->calificacion ?? '—' }}</dd>
                        </dl>
                    </li>
                @endforeach
            </ul>
        </x-tarjeta>
    @endif

    <x-tarjeta titulo="Historial" icono="bi-clock-history">
        @forelse ($historial as $clave => $resultados)
            @php $periodoHistorial = $resultados->first()->materia->grupo->periodo; @endphp
            {{-- "Ver detalle" cambia el selector de periodo a ese periodo y recarga esta pantalla. --}}
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 mb-2">
                <h3 class="fs-6 mb-0">{{ $periodoHistorial->nombre_visible }} <small class="mono texto-tenue">{{ $clave }}</small></h3>
                <form method="POST" action="{{ route('periodo-consulta.update') }}">
                    @csrf
                    @method('PUT')
                    <button type="submit" name="periodo" value="{{ $periodoHistorial->id }}" class="btn btn-sm btn-outline-siges">Ver detalle</button>
                </form>
            </div>
            <ul class="portal-lista">
                @foreach ($resultados as $resultado)
                    <li class="portal-item">
                        <div class="portal-item-encabezado">
                            <strong>{{ $resultado->materia->asignatura->nombre }}</strong>
                            <div class="portal-badges"><x-badge-estado :estado="$resultado->oportunidad" /><x-badge-estado :estado="$resultado->estado" /></div>
                        </div>
                        <p class="mb-0 mt-2">Definitiva <strong class="mono">{{ $resultado->calificacion }}</strong> <span class="texto-tenue">· {{ $resultado->fecha_cierre->format('d/m/Y') }}</span></p>
                    </li>
                @endforeach
            </ul>
        @empty
            <x-estado-vacio icono="bi-clock-history" texto="No hay resultados de otros periodos." />
        @endforelse
    </x-tarjeta>
</div>
@endsection
