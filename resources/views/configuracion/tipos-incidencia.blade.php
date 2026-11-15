@extends('configuracion._catalogo')
@php
    $titulo = 'Tipos de incidencia';
    $singular = 'tipo de incidencia';
    $catalogo = 'tipos-incidencia';
    $cambiaEstado = false;
    $casillas = ['notifica_tutor' => 'Notifica al tutor', 'justifica_falta' => 'Justifica falta'];
@endphp
@section('campos')
                <x-campo name="nombre" label="Nombre" col="col-12" :value="$registro->nombre" :requerido="true" maxlength="80" />
                <x-seleccion name="categoria" label="Categoría" col="col-12" :opciones="\App\Models\CatTipoIncidencia::CATEGORIAS" :valor="$registro->categoria" placeholder="Selecciona" :requerido="true"
                    ayuda="Suspensión e incapacidad cubren varios días: piden fecha de fin." />
                <x-seleccion name="nivel_gravedad" label="Gravedad" col="col-12" :opciones="\App\Models\CatTipoIncidencia::GRAVEDADES" :valor="$registro->nivel_gravedad" :requerido="true" />
                <div class="col-12">
                    @foreach ($casillas as $campo => $etiqueta)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="{{ $campo }}" name="{{ $campo }}" value="1" @checked(session()->hasOldInput() ? old($campo) : $registro->$campo)>
                            <label class="form-check-label" for="{{ $campo }}">{{ $etiqueta }}</label>
                        </div>
                    @endforeach
                    <div class="form-text">Justificar falta convierte en Justificadas las faltas y retardos de los días que cubre la incidencia.</div>
                </div>
@endsection

@section('tabla')
    <table class="table tabla-siges align-middle mb-0">
        <thead><tr><th scope="col">Nombre</th><th scope="col">Categoría</th><th scope="col">Gravedad</th><th scope="col">Notifica</th><th scope="col">Justifica falta</th><th scope="col">En uso</th><th scope="col" class="text-end">Acciones</th></tr></thead>
        <tbody>
            @foreach ($registros as $fila)
                <tr>
                    <td>{{ $fila->nombre }}</td>
                    <td>{{ $fila->categoria_nombre }}</td>
                    <td><x-badge-estado :estado="$fila->nivel_gravedad" /></td>
                    <td>{{ $fila->notifica_tutor ? 'Sí' : 'No' }}</td>
                    <td>{{ $fila->justifica_falta ? 'Sí' : 'No' }}</td>
                    <td>{{ $fila->incidencias_count }}</td>
                    <td>@include('configuracion._acciones', ['etiqueta' => $fila->nombre])</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
