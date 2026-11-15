// Campos que dependen de otro campo del formulario.
//   <select data-depende-de="idDelSelectPadre"> con <option data-padre="valor">: solo muestra las opciones del valor elegido.
//   <div data-ocultar-con="idDeLaCasilla">: se oculta (y sus campos se deshabilitan) mientras la casilla está marcada.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('select[data-depende-de]').forEach((select) => {
        const padre = document.getElementById(select.dataset.dependeDe);
        if (!padre) return;
        const filtrar = () => {
            select.querySelectorAll('option[data-padre]').forEach((opcion) => {
                const visible = opcion.dataset.padre === padre.value;
                opcion.hidden = !visible;
                opcion.disabled = !visible;
                if (!visible && opcion.selected) select.value = '';
            });
        };
        padre.addEventListener('change', filtrar);
        filtrar();
    });

    document.querySelectorAll('[data-ocultar-con]').forEach((bloque) => {
        const casilla = document.getElementById(bloque.dataset.ocultarCon);
        if (!casilla) return;
        const actualizar = () => {
            bloque.hidden = casilla.checked;
            bloque.querySelectorAll('input, select, textarea').forEach((campo) => { campo.disabled = casilla.checked; });
        };
        casilla.addEventListener('change', actualizar);
        actualizar();
    });
});
