import test from 'node:test';
import assert from 'node:assert/strict';
import { calcularDiferencia } from '../../public/js/conteo-fisico.js';

test('el conteo vacío no se confunde con cero cajas', () => {
    assert.deepEqual(calcularDiferencia('', 10), { texto: 'Sin contar', color: 'secondary' });
    assert.deepEqual(calcularDiferencia('0', 10), { texto: '-10 (Faltante)', color: 'danger' });
});

test('muestra la diferencia y el color para faltantes, sobrantes y coincidencias', () => {
    assert.deepEqual(calcularDiferencia('8', 10), { texto: '-2 (Faltante)', color: 'danger' });
    assert.deepEqual(calcularDiferencia('13', 10), { texto: '+3 (Sobrante)', color: 'warning' });
    assert.deepEqual(calcularDiferencia('10', 10), { texto: '0 (Coincide)', color: 'success' });
});

test('rechaza cantidades negativas, fracciones, texto y números fuera de rango', () => {
    for (const valor of ['-1', '1.5', 'abc', '1e2', '4294967296']) {
        assert.deepEqual(calcularDiferencia(valor, 10), { texto: 'Cantidad inválida', color: 'danger' });
    }
    assert.deepEqual(calcularDiferencia('4294967295', 10), { texto: '+4294967285 (Sobrante)', color: 'warning' });
});
