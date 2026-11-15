// <x-por-pagina>: el selector "Mostrar [n] por página" se envía al cambiar; sin JS queda el botón "Aplicar".
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-por-pagina]').forEach((formulario) => {
        const selector = formulario.querySelector('select');
        const boton = formulario.querySelector('[data-por-pagina-boton]');
        if (!selector) return;
        if (boton) boton.hidden = true;
        selector.addEventListener('change', () => formulario.requestSubmit ? formulario.requestSubmit() : formulario.submit());
    });
});
