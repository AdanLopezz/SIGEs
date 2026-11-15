{{-- Modal con formulario: título con icono, cuerpo (slot) y pie con Cancelar + acción.
     :confirmar → data-modal-confirmar (la acción y el nombre vienen del botón que lo abre; el botón inicia deshabilitado).
     :peligro → btn-danger, solo para confirmar acciones destructivas. formulario = id del <form> (para campos con form="…").
     :sin-boton → el pie solo lleva Cancelar (las opciones del cuerpo envían el formulario, p. ej. el selector de periodo).
     Los atributos extra van al <form>. --}}
@props(['id', 'titulo', 'icono' => null, 'accion' => null, 'metodo' => 'POST', 'boton' => 'Confirmar', 'peligro' => false, 'confirmar' => false, 'formulario' => null, 'sinBoton' => false])
<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Titulo" aria-hidden="true" @if ($confirmar) data-modal-confirmar @endif>
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title fs-5" id="{{ $id }}Titulo">@if ($icono)<i class="bi {{ $icono }}" aria-hidden="true"></i> @endif{{ $titulo }}</h2>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <form method="POST" @if ($formulario) id="{{ $formulario }}" @endif @if ($accion) action="{{ $accion }}" @endif {{ $attributes }}>
            @csrf
            @if (strtoupper($metodo) !== 'POST') @method($metodo) @endif
            <div class="modal-body">{{ $slot }}</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                @unless ($sinBoton)<button type="submit" class="btn {{ $peligro ? 'btn-danger' : 'btn-siges' }}" @disabled($confirmar)>{{ $boton }}</button>@endunless
            </div>
        </form>
    </div></div>
</div>
