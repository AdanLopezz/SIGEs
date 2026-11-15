// Usuario generado por el sistema (<x-campo-usuario>): "Regenerar" pide otro al servidor.
//   data-nombre / data-apellido: id de los campos de donde se toman el nombre y el primer apellido.
//   data-automatico: se genera solo al cambiar esos campos (altas).
//   Dentro de un modal, el valor y el nombre llegan del botón que lo abre:
//   data-usuario, data-persona-nombre y data-persona-apellido.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-usuario-generado]').forEach((campo) => {
        const campoNombre = campo.dataset.nombre ? document.getElementById(campo.dataset.nombre) : null;
        const campoApellido = campo.dataset.apellido ? document.getElementById(campo.dataset.apellido) : null;
        const botones = document.querySelectorAll(`[data-regenerar-usuario="${campo.id}"]`);
        let nombre = () => campoNombre?.value ?? '';
        let apellido = () => campoApellido?.value ?? '';

        const generar = async () => {
            const datos = { nombre: nombre().trim(), primer_apellido: apellido().trim() };
            if (!datos.nombre || !datos.primer_apellido) return;
            botones.forEach((boton) => { boton.disabled = true; });
            try {
                const { data } = await window.axios.get(campo.dataset.url, { params: datos });
                campo.value = data.usuario;
            } catch {
                // Si falla, se queda el valor actual; el servidor genera otro al guardar si hace falta.
            } finally {
                botones.forEach((boton) => { boton.disabled = false; });
            }
        };

        botones.forEach((boton) => boton.addEventListener('click', generar));

        if (campo.dataset.automatico !== undefined) {
            [campoNombre, campoApellido].forEach((origen) => origen?.addEventListener('change', generar));
            if (!campo.value) generar();
        }

        const modal = campo.closest('.modal');
        modal?.addEventListener('show.bs.modal', (evento) => {
            const boton = evento.relatedTarget;
            campo.value = boton?.dataset.usuario ?? '';
            nombre = () => boton?.dataset.personaNombre ?? '';
            apellido = () => boton?.dataset.personaApellido ?? '';
        });
    });
});
