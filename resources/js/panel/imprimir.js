// Botón <button data-imprimir> que abre el diálogo de impresión del navegador.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-imprimir]').forEach((boton) => {
        boton.addEventListener('click', () => window.print());
    });
});
