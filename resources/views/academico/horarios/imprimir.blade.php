@extends('layouts.impresion')
@use('App\Models\AcaHorario')
@section('titulo', 'Horario · '.$titulo)
@section('contenido')
    <div class="impresion-contenedor">
        <div class="impresion-acciones no-imprimir">
            <a href="{{ $volver }}" class="volver mb-0"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver</a>
            <button type="button" class="btn btn-siges" data-imprimir><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</button>
        </div>

        <article class="horario-hoja" aria-label="Horario para imprimir">
            <header>
                <strong>Horario · {{ $titulo }}</strong>
                <small>{{ $periodo?->nombre_visible }} ({{ $periodo?->clave }})@if ($grupo) · Turno {{ $grupo->turno->nombre }} · Orientador: {{ $grupo->orientador?->nombre }} · Tutor: {{ $grupo->tutor?->nombre }}@endif</small>
                <small>{{ $cuadricula->clases->count() }} {{ $cuadricula->clases->count() === 1 ? 'clase' : 'clases' }} · {{ AcaHorario::duracion($cuadricula->clases->sum('minutos')) }} por semana · Impreso el {{ now()->format('d/m/Y H:i') }}</small>
            </header>
            @if ($cuadricula->vacia())
                <p>Sin clases en el horario.</p>
            @else
                @include('academico.horarios._cuadricula', ['mostrar' => $tipo === 'grupo' ? ['docente', 'aula'] : ['grupo', 'aula']])
            @endif
        </article>
    </div>
@endsection
