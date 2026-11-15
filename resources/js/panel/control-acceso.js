// Control de acceso: el lector QR (USB o Bluetooth) escribe en el campo y manda Enter; la cámara usa @zxing/browser.
// Cada lectura se envía con fetch y se muestran el resultado y los últimos registros sin recargar la página.
const SEGUNDOS_RESULTADO = 5;
const MS_ENTRE_LECTURAS_CAMARA = 3000;

document.addEventListener('DOMContentLoaded', () => {
    const formulario = document.querySelector('[data-control-acceso]');
    if (!formulario) return;

    const campo = formulario.querySelector('[name="codigo"]');
    const resultado = document.querySelector('[data-resultado]');
    const ultimos = document.querySelector('[data-ultimos]');
    let temporizador = null;
    let enviando = false;

    const enfocar = () => campo.focus();
    const ocultarDespues = () => {
        clearTimeout(temporizador);
        temporizador = setTimeout(() => resultado.querySelector('[data-resultado-acceso]')?.setAttribute('hidden', ''), SEGUNDOS_RESULTADO * 1000);
    };

    const registrar = async (codigo) => {
        if (enviando || !codigo.trim()) return;
        enviando = true;
        try {
            const { data } = await window.axios.post(formulario.action, { codigo }, { headers: { Accept: 'application/json' } });
            resultado.innerHTML = data.resultado;
            ultimos.innerHTML = data.ultimos;
        } catch (error) {
            const mensaje = error.response?.status === 419 ? 'La sesión expiró. Recarga la página.' : 'No se pudo registrar la lectura. Inténtalo de nuevo.';
            resultado.innerHTML = `<div class="acceso-resultado error" role="alert" data-resultado-acceso><p class="acceso-estado mb-0">${mensaje}</p></div>`;
        } finally {
            enviando = false;
            campo.value = '';
            enfocar();
            ocultarDespues();
        }
    };

    formulario.addEventListener('submit', (evento) => {
        evento.preventDefault();
        registrar(campo.value);
    });
    // El resultado del registro manual (recarga normal) también se oculta solo.
    if (resultado.querySelector('[data-resultado-acceso]')) ocultarDespues();

    // Cámara
    const boton = formulario.querySelector('[data-usar-camara]');
    const contenedor = formulario.querySelector('[data-camara]');
    const aviso = formulario.querySelector('[data-aviso-camara]');
    let controles = null;
    let ultimaLectura = { texto: '', hora: 0 };

    const detener = () => {
        controles?.stop();
        controles = null;
        contenedor.classList.remove('activa');
        boton.querySelector('span').textContent = 'Usar cámara';
        enfocar();
    };

    boton.addEventListener('click', async () => {
        if (controles) return detener();
        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
            aviso.hidden = false;
            return enfocar();
        }
        try {
            // Se carga solo al usarla, para no pesar en las demás pantallas.
            const { BrowserQRCodeReader } = await import('@zxing/browser');
            contenedor.classList.add('activa');
            boton.querySelector('span').textContent = 'Detener cámara';
            controles = await new BrowserQRCodeReader().decodeFromVideoDevice(undefined, contenedor.querySelector('video'), (lectura) => {
                if (!lectura) return;
                const texto = lectura.getText();
                const ahora = Date.now();
                // La cámara lee el mismo QR muchas veces por segundo.
                if (texto === ultimaLectura.texto && ahora - ultimaLectura.hora < MS_ENTRE_LECTURAS_CAMARA) return;
                ultimaLectura = { texto, hora: ahora };
                registrar(texto);
            });
        } catch (error) {
            detener();
            aviso.hidden = false;
            // Solo el texto: el icono del aviso se conserva.
            (aviso.querySelector('[data-aviso-texto]') ?? aviso).textContent = 'No se pudo abrir la cámara: revisa el permiso del navegador o usa el lector.';
        }
    });
});
