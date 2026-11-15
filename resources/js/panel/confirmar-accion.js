document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-modal-confirmar]').forEach((modal) => {
        const formulario = modal.querySelector('form');
        const confirmar = formulario?.querySelector('[type="submit"]');
        if (!formulario || !confirmar) return;

        modal.addEventListener('show.bs.modal', (evento) => {
            const boton = evento.relatedTarget;
            const accion = boton?.dataset.accion;
            confirmar.disabled = !accion;
            formulario.removeAttribute('action');
            if (accion) formulario.action = accion;
            modal.querySelectorAll('[data-campo]').forEach((campo) => {
                campo.textContent = boton?.dataset[campo.dataset.campo] ?? '';
            });
        });

        modal.addEventListener('hidden.bs.modal', () => {
            formulario.removeAttribute('action');
            confirmar.disabled = true;
        });
    });
});
