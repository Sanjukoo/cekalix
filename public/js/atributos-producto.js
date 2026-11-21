/*
 * Campos de atributos en la ficha del producto (crear y editar).
 *
 * Cada atributo llega como { nombre, campo, opciones }:
 *   - con opciones: lista desplegable con esos valores;
 *   - sin opciones: campo de texto libre.
 * Si el producto ya tenía un valor que no está en la lista, se agrega como
 * "(valor actual)" para no perderlo al guardar.
 */
window.CekalixAtributos = {
    crearCampo: function (atributo, valorActual) {
        const nombreCampo = 'atributo_' + atributo.campo;
        const valor = valorActual == null ? '' : String(valorActual);
        const opciones = Array.isArray(atributo.opciones) ? atributo.opciones : [];

        const columna = document.createElement('div');
        columna.className = 'col-md-6 mb-3';

        const label = document.createElement('label');
        label.className = 'form-label';
        label.htmlFor = nombreCampo;
        label.textContent = atributo.nombre;

        let campo;

        if (opciones.length) {
            campo = document.createElement('select');
            campo.className = 'form-select';
            campo.add(new Option('Seleccionar ' + atributo.nombre.toLowerCase(), ''));

            opciones.forEach(function (opcion) {
                campo.add(new Option(opcion, opcion));
            });

            if (valor !== '' && opciones.indexOf(valor) === -1) {
                campo.add(new Option(valor + ' (valor actual)', valor));
            }

            campo.value = valor;
        } else {
            campo = document.createElement('input');
            campo.type = 'text';
            campo.className = 'form-control';
            campo.maxLength = 255;
            campo.value = valor;
        }

        campo.id = nombreCampo;
        campo.name = nombreCampo;

        columna.append(label, campo);

        return columna;
    },

    // Dibuja todos los atributos de una categoría dentro del contenedor.
    dibujar: function (contenedor, tituloCategoria, atributos, valores) {
        contenedor.replaceChildren();

        if (!atributos || atributos.length === 0) {
            const aviso = document.createElement('p');
            aviso.className = 'text-muted';
            aviso.textContent = 'Esta categoría no tiene atributos definidos.';
            contenedor.append(aviso);
            return;
        }

        const titulo = document.createElement('h6');
        titulo.className = 'text-danger';
        titulo.textContent = 'Atributos de ' + tituloCategoria;

        const fila = document.createElement('div');
        fila.className = 'row';

        atributos.forEach(function (atributo) {
            fila.append(window.CekalixAtributos.crearCampo(atributo, (valores || {})[atributo.campo]));
        });

        contenedor.append(titulo, fila);
    }
};
