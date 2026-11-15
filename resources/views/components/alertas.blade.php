{{-- Mensajes de la sesión: status (éxito) y errores de acciones (accion). Se pueden cerrar. --}}
@if (session('status'))
    <div class="alerta-siges exito alert alert-dismissible fade show" role="status">
        <i class="bi bi-check-circle" aria-hidden="true"></i>
        <div>{{ session('status') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar aviso"></button>
    </div>
@endif
@error('accion')
    <div class="alerta-siges peligro alert alert-dismissible fade show" role="alert">
        <i class="bi bi-x-octagon" aria-hidden="true"></i>
        <div>{{ $message }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar aviso"></button>
    </div>
@enderror
