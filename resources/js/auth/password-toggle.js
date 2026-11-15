document.addEventListener('DOMContentLoaded', () => {
    // Mostrar u ocultar la contraseña
    document.querySelectorAll('.password-toggle').forEach((button) => {

        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordTarget);

            if (!input) {
                return;
            }

            const mostrar = input.type === 'password';
            input.type = mostrar ? 'text' : 'password';

            // La etiqueta cambia según el estado (accesibilidad)
            button.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });

    });


});
