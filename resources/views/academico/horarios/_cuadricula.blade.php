{{-- Cuadrícula semanal (App\Support\CuadriculaHorario). Recibe $cuadricula y, opcionales:
     $mostrar (lista de 'grupo', 'docente', 'aula'; la materia siempre se muestra), $agregar = fn (dia, inicio) => url para una celda vacía,
     $editar = fn (clase) => url de una clase y $resaltar = id de la clase que se edita. --}}
@php
    $mostrar ??= ['docente', 'aula'];
    $agregar ??= null;
    $editar ??= null;
    $resaltar ??= null;
@endphp
<div class="table-responsive">
    <table class="horario-tabla mb-0" aria-label="Horario semanal">
        <thead><tr><th scope="col" class="horario-hora">Hora</th>@foreach ($cuadricula->dias as $nombreDia)<th scope="col">{{ $nombreDia }}</th>@endforeach</tr></thead>
        <tbody>
        @foreach ($cuadricula->filas as $fila)
            <tr>
                <th scope="row" class="horario-hora">{{ $fila['inicio'] }}<small>{{ $fila['fin'] }}</small></th>
                @foreach ($fila['celdas'] as $dia => $celda)
                    @if ($celda === 'cubierta')
                        @continue
                    @elseif ($celda === null)
                        <td>
                            @if ($agregar)
                                <a class="horario-vacia" href="{{ $agregar($dia, $fila['inicio']) }}" aria-label="Agregar clase el {{ $cuadricula->dias[$dia] }} a las {{ $fila['inicio'] }}"><i class="bi bi-plus-lg" aria-hidden="true"></i></a>
                            @endif
                        </td>
                    @else
                        <td rowspan="{{ $celda['filas'] }}">
                            @foreach ($celda['clases'] as $clase)
                                @php
                                    $url = $editar ? $editar($clase) : null;
                                    $etiqueta = $clase->materia->asignatura->nombre.', '.$clase->dia_nombre.' de '.$clase->inicio.' a '.$clase->fin;
                                @endphp
                                @if ($url)<a href="{{ $url }}" @class(['horario-clase', 'actual' => $resaltar === $clase->id]) aria-label="Editar {{ $etiqueta }}">@else<div class="horario-clase" aria-label="{{ $etiqueta }}">@endif
                                    <strong>{{ $clase->materia->asignatura->nombre }}</strong>
                                    <small>{{ $clase->inicio }} – {{ $clase->fin }}</small>
                                    @if (in_array('grupo', $mostrar, true))<small>Grupo {{ $clase->materia->grupo->identificador }}</small>@endif
                                    @if (in_array('docente', $mostrar, true))<small>{{ $clase->materia->docente?->nombre }}</small>@endif
                                    @if (in_array('aula', $mostrar, true))<small><i class="bi bi-door-closed" aria-hidden="true"></i> {{ $clase->espacio->nombre }}</small>@endif
                                @if ($url)</a>@else</div>@endif
                            @endforeach
                        </td>
                    @endif
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
