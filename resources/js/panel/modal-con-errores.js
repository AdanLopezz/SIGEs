import { Modal } from 'bootstrap';

// El modal cuyo formulario lleva data-abrir-al-cargar se abre al cargar la página
// (regresó con errores de validación). <x-modal> pasa los atributos extra al <form>.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-abrir-al-cargar]').forEach((formulario) => {
        const modal = formulario.closest('.modal');
        if (modal) Modal.getOrCreateInstance(modal).show();
    });
});
