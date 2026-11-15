@extends('layouts.app')
@use('App\Models\AcaHorario')
@section('titulo', 'Horario')
@section('contenido')
<div class="portal">
    <x-page-header titulo="Horario" subtitulo="Clases de la semana del periodo elegido: materia, hora, aula y docente." />
    @include('portal._encabezado')

    @if ($porDia->isEmpty())
        <x-tarjeta><x-estado-vacio icono="bi-calendar-week" texto="Todavía no hay horario para este periodo." /></x-tarjeta>
    @else
        @foreach (AcaHorario::DIAS as $numero => $nombreDia)
            @continue (! $porDia->has($numero))
            <section class="horario-dia" aria-label="{{ $nombreDia }}">
                <h2>{{ $nombreDia }}</h2>
                <ul class="portal-lista">
                    @foreach ($porDia->get($numero) as $clase)
                        <li class="portal-item">
                            <div class="portal-item-encabezado">
                                <strong>{{ $clase->materia->asignatura->nombre }}</strong>
                                <span class="mono">{{ $clase->inicio }} – {{ $clase->fin }}</span>
                            </div>
                            <dl class="dato-lista">
                                <dt>Aula</dt><dd>{{ $clase->espacio->nombre }}</dd>
                                <dt>Docente</dt><dd>{{ $clase->materia->docente?->nombre }}</dd>
                                @if ($clase->materia->aca_grupo_escolar_id !== $inscripcionEncabezado?->aca_grupo_escolar_id)<dt>Grupo</dt><dd>{{ $clase->materia->grupo->identificador }} (recursamiento)</dd>@endif
                            </dl>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    @endif
</div>
@endsection
