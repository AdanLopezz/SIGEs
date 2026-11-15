import { Modal } from 'bootstrap';

// Calificaciones y pesos se suman en centésimas (enteros), igual que en el servidor.
const centesimos = (texto) => Math.round(parseFloat(texto) * 100);
const esNumero = (texto) => texto !== '' && texto !== undefined && !Number.isNaN(parseFloat(texto));

// Promedio ponderado de una fila (solo visual; el servidor recalcula al cerrar). Si faltan calificaciones, es parcial.
function actualizarPromedio(tabla, fila) {
    const decimales = Number(tabla.dataset.decimales ?? 1);
    const redondear = tabla.dataset.redondear === '1';
    const minima = centesimos(tabla.dataset.minima ?? '6');
    let suma = 0;
    let pesoCapturado = 0;
    let pesoTotal = 0;
    fila.querySelectorAll('[data-evaluacion]').forEach((celda) => {
        const peso = centesimos(celda.dataset.peso);
        const campo = celda.querySelector('input[data-calificacion]');
        const texto = campo ? campo.value : celda.dataset.valor;
        pesoTotal += peso;
        if (!esNumero(texto)) return;
        suma += centesimos(texto) * peso;
        pesoCapturado += peso;
    });

    const salida = fila.querySelector('[data-promedio]');
    salida.classList.remove('texto-tenue', 'texto-peligro');
    if (pesoCapturado === 0) {
        salida.textContent = '—';
        return;
    }
    if (pesoCapturado < pesoTotal || pesoTotal !== 10000) {
        salida.textContent = `${(suma / pesoCapturado / 100).toFixed(decimales)} (parcial)`;
        salida.classList.add('texto-tenue');
        return;
    }
    // Completa: Σ(valor × peso) / 100 en millonésimas, con el redondeo o truncado de config/siges.php.
    const divisor = 10 ** (6 - decimales);
    const escalada = redondear ? Math.floor((suma + divisor / 2) / divisor) : Math.floor(suma / divisor);
    salida.textContent = (escalada / 10 ** decimales).toFixed(decimales);
    if (escalada * 10 ** (2 - decimales) < minima) salida.classList.add('texto-peligro');
}

function iniciarTabla(tabla) {
    const formulario = document.getElementById(tabla.dataset.formulario);
    // Orden por columna: Tab y Enter bajan al siguiente alumno; al final de la columna pasan a la siguiente evaluación.
    const campos = [...tabla.querySelectorAll('input[data-calificacion]')]
        .sort((a, b) => (a.dataset.columna - b.dataset.columna) || (a.dataset.fila - b.dataset.fila));
    const filas = tabla.querySelectorAll('[data-fila-calificaciones]');
    filas.forEach((fila) => actualizarPromedio(tabla, fila));

    const cambiados = () => campos.filter((campo) => campo.value !== '' && campo.value !== campo.dataset.original);
    let enviando = false;

    tabla.addEventListener('input', (evento) => {
        const campo = evento.target.closest('input[data-calificacion]');
        if (campo) actualizarPromedio(tabla, campo.closest('[data-fila-calificaciones]'));
    });

    tabla.addEventListener('keydown', (evento) => {
        const campo = evento.target.closest('input[data-calificacion]');
        if (!campo || (evento.key !== 'Enter' && evento.key !== 'Tab')) return;
        const destino = campos[campos.indexOf(campo) + (evento.shiftKey ? -1 : 1)];
        // Enter nunca envía el formulario desde la tabla; Tab en la última celda sigue su camino normal.
        if (destino || evento.key === 'Enter') evento.preventDefault();
        if (destino) {
            destino.focus();
            destino.select();
        }
    });

    if (!formulario) return;
    const modal = document.getElementById('modalMotivo');
    const motivo = formulario.querySelector('[name="motivo"]');

    // Corregir una evaluación cerrada (o guardar sin poder capturar) exige el motivo: se pide en el modal antes de enviar.
    formulario.addEventListener('submit', (evento) => {
        const requiereMotivo = formulario.dataset.motivoSiempre === '1'
            ? cambiados().length > 0
            : cambiados().some((campo) => campo.dataset.cerrada === '1');
        if (requiereMotivo && motivo && motivo.value.trim() === '') {
            evento.preventDefault();
            Modal.getOrCreateInstance(modal).show();
            return;
        }
        enviando = true;
    });
    modal?.addEventListener('shown.bs.modal', () => motivo?.focus());

    // Aviso al salir (o al cerrar una evaluación) con cambios sin guardar.
    window.addEventListener('beforeunload', (evento) => {
        if (!enviando && cambiados().length > 0) evento.preventDefault();
    });
}

// Programar evaluaciones: total de los pesos en vivo.
function iniciarPesos(formulario) {
    const total = formulario.querySelector('[data-suma-pesos]');
    const sumar = () => {
        const suma = [...formulario.querySelectorAll('input[data-peso]')]
            .reduce((acumulado, campo) => acumulado + (esNumero(campo.value) ? centesimos(campo.value) : 0), 0);
        total.textContent = (suma / 100).toFixed(2);
        total.classList.toggle('texto-peligro', suma !== 10000);
    };
    formulario.addEventListener('input', sumar);
    sumar();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-tabla-calificaciones]').forEach(iniciarTabla);
    document.querySelectorAll('[data-pesos-evaluacion]').forEach(iniciarPesos);
});
