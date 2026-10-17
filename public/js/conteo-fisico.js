export function calcularDiferencia(valor, facturadas) {
    if (valor === '') {
        return { texto: 'Sin contar', color: 'secondary' };
    }

    if (!/^\d+$/.test(valor) || Number(valor) > 4294967295) {
        return { texto: 'Cantidad inválida', color: 'danger' };
    }

    const diferencia = Number(valor) - facturadas;
    if (diferencia < 0) {
        return { texto: `${diferencia} (Faltante)`, color: 'danger' };
    }
    if (diferencia > 0) {
        return { texto: `+${diferencia} (Sobrante)`, color: 'warning' };
    }
    return { texto: '0 (Coincide)', color: 'success' };
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('[data-conteo-fila]').forEach((fila) => {
        const input = fila.querySelector('input');
        const output = fila.querySelector('[data-conteo-diferencia]');
        const actualizar = () => {
            // Algunos navegadores exponen como vacío un número incompleto o inválido.
            const resultado = input.validity.badInput
                ? { texto: 'Cantidad inválida', color: 'danger' }
                : calcularDiferencia(input.value, Number(fila.dataset.facturadas));
            output.textContent = resultado.texto;
            output.className = `badge text-bg-${resultado.color}`;
        };
        input.addEventListener('input', actualizar);
        actualizar();
    });
}
