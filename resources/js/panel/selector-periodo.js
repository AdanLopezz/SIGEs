// Modal "Selecciona periodo": invierte el orden de la lista (más reciente / más antiguo primero) sin recargar.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-ordenar-periodos]').forEach((boton) => {
        const lista = boton.closest('.modal')?.querySelector('[data-lista-periodos]');
        const texto = boton.querySelector('[data-orden-texto]');
        const icono = boton.querySelector('.bi');
        if (!lista) return;
        boton.addEventListener('click', () => {
            [...lista.children].reverse().forEach((elemento) => lista.appendChild(elemento));
            const antiguoPrimero = boton.getAttribute('aria-pressed') !== 'true';
            boton.setAttribute('aria-pressed', String(antiguoPrimero));
            if (texto) texto.textContent = antiguoPrimero ? 'Más antiguo primero' : 'Más reciente primero';
            icono?.classList.toggle('bi-sort-down', !antiguoPrimero);
            icono?.classList.toggle('bi-sort-up', antiguoPrimero);
        });
    });
});
