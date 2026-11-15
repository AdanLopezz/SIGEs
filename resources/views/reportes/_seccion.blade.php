{{-- Tabla de una sección de reporte (pantalla e impresión). $seccion: titulo, columnas, filas, porcentajes, grafica, listado, vacio. --}}
@use('App\Support\Reportes\Catalogo')
@if ($seccion['grafica'])
    <x-barras :filas="$seccion['filas']" :etiqueta="$seccion['grafica']['etiqueta']" :valor="$seccion['grafica']['valor']"
        :porcentaje="in_array($seccion['grafica']['valor'], $seccion['porcentajes'], true)" :titulo="'Gráfica: '.$seccion['titulo']" />
@endif
@if (collect($seccion['filas'])->isEmpty())
    <x-estado-vacio icono="bi-bar-chart" :texto="$seccion['vacio']" />
@else
    <div class="table-responsive">
        <table class="table tabla-siges align-middle mb-0">
            <thead><tr>
                @foreach ($seccion['columnas'] as $indice => $columna)
                    <th scope="col" @class(['text-end' => $indice > 0 && ! $seccion['listado']])>{{ $columna }}</th>
                @endforeach
            </tr></thead>
            <tbody>
            @foreach ($seccion['filas'] as $fila)
                <tr>
                    @foreach (array_values($fila) as $indice => $valor)
                        <td @class(['text-end' => $indice > 0 && ! $seccion['listado'], 'text-nowrap' => is_numeric($valor) || $valor === null])>{{ Catalogo::celda($valor, in_array($indice, $seccion['porcentajes'], true)) }}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
