@extends('layouts.app')
@php $editando = $convenio->exists; @endphp
@section('titulo', $editando ? 'Editar convenio' : 'Nuevo convenio')
@section('contenido')
    <x-page-header :titulo="$editando ? 'Editar convenio '.$convenio->numero : 'Nuevo convenio'"
        :subtitulo="$editando ? 'Solo se edita mientras está vigente. Los cambios quedan en la bitácora.' : 'Registra el convenio con su documento firmado.'"
        :volver="$editando ? [route('convenios.show', $convenio), 'Volver al convenio'] : [route('convenios.index'), 'Volver a Convenios']" />
    <x-errores-formulario />

    @if ($empresas->isEmpty())
        <x-aviso tipo="aviso">Primero registra la empresa en <a href="{{ route('empresas.create') }}">Empresas</a>.</x-aviso>
    @endif

    <form method="POST" action="{{ $editando ? route('convenios.update', $convenio) : route('convenios.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @if ($editando) @method('PUT') @endif
        <section class="form-seccion">
            <h2 class="form-seccion-titulo"><span>1</span> Convenio</h2>
            <div class="row g-3">
                <x-seleccion name="org_empresa_id" label="Empresa" col="col-md-8" :opciones="$empresas" :valor="$convenio->org_empresa_id" placeholder="Selecciona una empresa" :requerido="true" />
                <x-campo name="numero" label="Número de convenio" :value="$convenio->numero" maxlength="40" class="text-uppercase mono" :requerido="true" />
                <x-seleccion name="tipo" label="Tipo" :opciones="$tipos" :valor="$convenio->tipo" placeholder="Selecciona un tipo" :requerido="true" />
                <x-campo name="fecha_inicio" label="Fecha de inicio" type="date" :value="$convenio->fecha_inicio?->format('Y-m-d')" :requerido="true" />
                <x-campo name="fecha_fin" label="Fecha de fin" type="date" :value="$convenio->fecha_fin?->format('Y-m-d')" :requerido="true" ayuda="Posterior a la de inicio. Al pasar esta fecha queda Vencido." />
            </div>
        </section>
        <section class="form-seccion">
            <h2 class="form-seccion-titulo"><span>2</span> Convenio firmado</h2>
            @if ($editando)<p>Documento actual: <x-archivo-enlace :archivo="$convenio->documento" /></p>@endif
            <div class="row g-3">
                <x-archivo-input name="documento" :label="$editando ? 'Reemplazar documento' : 'Convenio firmado'" col="col-md-6" :requerido="! $editando"
                    :ayuda="$editando ? 'Opcional. PDF o imagen, máximo 10 MB. El anterior se conserva.' : 'Obligatorio. PDF o imagen, máximo 10 MB.'" />
            </div>
        </section>
        @if ($editando)
            <section class="form-seccion">
                <h2 class="form-seccion-titulo"><span>3</span> Motivo del cambio</h2>
                <div class="row g-3"><x-campo name="motivo" label="Motivo del cambio" col="col-12" maxlength="255" :requerido="true" ayuda="Queda registrado en la bitácora junto con los cambios." /></div>
            </section>
        @endif
        <div class="form-acciones">
            <a href="{{ $editando ? route('convenios.show', $convenio) : route('convenios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-siges">{{ $editando ? 'Guardar cambios' : 'Registrar convenio' }}</button>
        </div>
    </form>
@endsection
