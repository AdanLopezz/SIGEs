@extends('layouts.app')
@section('titulo', 'Cambio de periodo · '.$grupo->identificador)
@section('contenido')
    <x-page-header :titulo="'Revisar grupo '.$grupo->identificador" :volver="[route('cambio-periodo.alumnos'), 'Volver a Alumnos']">
        <x-slot:subtitulo>{{ $grupo->semestre }}.º · {{ $grupo->carrera->nombre }} · {{ $grupo->turno->nombre }}. De {{ $cambio->origen->nombre_visible }} ({{ $cambio->origen->clave }}) a {{ $cambio->destino->nombre_visible }} ({{ $cambio->destino->clave }}).</x-slot:subtitulo>
    </x-page-header>
    @include('control-escolar.cambio-periodo._pasos', ['paso' => 3])
    <x-errores-formulario />

    @if ($sinCerrar > 0)
        <x-aviso tipo="aviso">Este grupo tiene {{ $sinCerrar }} {{ $sinCerrar === 1 ? 'materia' : 'materias' }} sin cerrar: los adeudos pueden estar incompletos.</x-aviso>
    @endif
    @if ($grupo->esUltimoSemestre())
        <x-aviso tipo="info">Es el último semestre de la carrera: sus alumnos no se reinscriben y quedan como candidatos a egreso.</x-aviso>
    @endif

    <x-tarjeta sin-relleno etiqueta="Alumnos del grupo">
        @if ($filas->isEmpty())
            <x-estado-vacio icono="bi-mortarboard" texto="El grupo no tiene alumnos inscritos." />
        @else
            <form method="POST" action="{{ route('cambio-periodo.alumnos.guardar', $grupo) }}" id="formRevision">
                @csrf
                @method('PUT')
                <div class="table-responsive">
                    <table class="table tabla-siges align-middle mb-0">
                        <thead><tr><th scope="col">Reinscribir</th><th scope="col">Alumno</th><th scope="col">Estado</th><th scope="col">Adeudos</th><th scope="col">Situación</th><th scope="col">Grupo destino</th></tr></thead>
                        <tbody>
                        @foreach ($filas as $fila)
                            @php $id = $fila->alumno->id; $nombre = $fila->alumno->persona->nombre_completo; @endphp
                            <tr>
                                <td>
                                    @if ($fila->elegible)
                                        <input type="hidden" name="alumnos[{{ $id }}][marcado]" value="0">
                                        <input class="form-check-input" type="checkbox" name="alumnos[{{ $id }}][marcado]" value="1" @checked($fila->marcado) aria-label="Reinscribir a {{ $nombre }}">
                                    @else
                                        <input class="form-check-input" type="checkbox" disabled aria-label="{{ $nombre }} no se reinscribe">
                                    @endif
                                </td>
                                <td><strong>{{ $nombre }}</strong><small class="d-block mono">{{ $fila->alumno->matricula }}</small></td>
                                <td><x-badge-estado :estado="$fila->alumno->estado->nombre" /></td>
                                <td>@if ($fila->adeudos > $maximo)<x-badge-estado tono="peligro" :texto="$fila->adeudos.' (máx. '.$maximo.')'" />@else{{ $fila->adeudos }}@endif</td>
                                <td><x-badge-estado :estado="$fila->situacion" />@if ($fila->motivo)<small class="d-block texto-tenue">{{ $fila->motivo }}</small>@endif</td>
                                <td>
                                    @if (! $fila->elegible)
                                        —
                                    @elseif ($fila->opciones === [])
                                        <span class="texto-peligro">Sin grupo destino</span>
                                    @else
                                        <select name="alumnos[{{ $id }}][destino]" class="form-select form-select-sm" aria-label="Grupo destino de {{ $nombre }}">
                                            @foreach ($fila->opciones as $valor => $texto)<option value="{{ $valor }}" @selected((string) $fila->destino === (string) $valor)>{{ $texto }}</option>@endforeach
                                        </select>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="tabla-pie"><span>{{ $filas->count() }} {{ $filas->count() === 1 ? 'alumno' : 'alumnos' }} · {{ $filas->where('marcado', true)->count() }} marcados</span></div>
            </form>
        @endif
    </x-tarjeta>

    <div class="form-acciones">
        <a href="{{ route('cambio-periodo.alumnos') }}" class="btn btn-outline-secondary">Cancelar</a>
        @if ($filas->where('elegible', true)->isNotEmpty())
            <button type="submit" form="formRevision" class="btn btn-siges">Guardar revisión</button>
        @endif
    </div>
@endsection
