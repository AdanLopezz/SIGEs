document.addEventListener('DOMContentLoaded', () => {
    // Menú lateral en tablet y celular: se abre con ☰ y se cierra tocando fuera
    const toggle = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');

    toggle?.addEventListener('click', () => {
        const abierto = document.body.classList.toggle('sidebar-open');
        toggle.setAttribute('aria-label', abierto ? 'Cerrar menú' : 'Abrir menú');
    });

    backdrop?.addEventListener('click', () => {
        document.body.classList.remove('sidebar-open');
        toggle?.setAttribute('aria-label', 'Abrir menú');
    });

    // Cerrar el menú con la tecla Esc
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
            document.body.classList.remove('sidebar-open');
            toggle?.setAttribute('aria-label', 'Abrir menú');
        }
    });


});
