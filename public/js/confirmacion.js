/*
 * Confirmación de acciones en CEKALIX.
 *
 * Un formulario pide confirmación con data-confirm="Mensaje". Antes de enviarlo
 * se abre una ventana con un resumen de los datos que se van a guardar.
 *
 * Atributos del <form>:
 *   data-confirm          Mensaje principal (obligatorio para activar la confirmación).
 *   data-confirm-titulo   Título de la ventana. Por defecto: "Confirmar acción".
 *   data-confirm-boton    Texto del botón de confirmar. Por defecto: "Confirmar".
 *   data-confirm-datos    JSON {"Etiqueta": "valor"} con datos fijos (por ejemplo, qué se elimina).
 *   data-confirm-resumen="no"  No leer los campos del formulario.
 *
 * Atributos dentro del formulario:
 *   data-resumen="Etiqueta"       Nombre a mostrar para un campo (si no, se usa su <label>).
 *   data-resumen="no"             Excluir un campo (o todo un bloque).
 *   data-resumen-grupo="Título"   Bloque con filas repetidas (productos, SKU...).
 *   data-resumen-fila             Cada fila del bloque anterior; se muestra como tabla.
 *   data-resumen-valor="Etiqueta" Texto calculado que también se muestra (subtotal, total...).
 */
document.addEventListener('DOMContentLoaded', function () {
    const modalElemento = document.getElementById('modalConfirmacion');

    if (!modalElemento || typeof bootstrap === 'undefined') return;

    const modal = new bootstrap.Modal(modalElemento);
    const dialogo = modalElemento.querySelector('.modal-dialog');
    const titulo = document.getElementById('tituloConfirmacion');
    const mensaje = document.getElementById('mensajeConfirmacion');
    const resumen = document.getElementById('resumenConfirmacion');
    const btnAceptar = document.getElementById('btnAceptarConfirmacion');

    const LARGO_MAXIMO = 140;

    let formularioPendiente = null;
    let botonPendiente = null;

    // ---------- Lectura de campos ----------

    function limpiar(texto) {
        return String(texto ?? '')
            .replace(/\s+/g, ' ')
            .replace(/\s*\*\s*$/, '')
            .trim();
    }

    function recortar(texto) {
        return texto.length > LARGO_MAXIMO
            ? texto.slice(0, LARGO_MAXIMO - 1) + '…'
            : texto;
    }

    function estaVisible(elemento) {
        return elemento.getClientRects().length > 0;
    }

    function excluido(elemento) {
        return elemento.closest('[data-resumen="no"]') !== null;
    }

    function etiquetaDe(campo) {
        if (campo.dataset.resumen) return limpiar(campo.dataset.resumen);

        if (campo.labels && campo.labels.length) {
            return limpiar(campo.labels[0].textContent);
        }

        // Etiqueta dentro de la misma columna (filas sin atributo "for").
        const columna = campo.closest('[class*="col-"], .mb-3');
        const label = columna?.querySelector('.form-label');
        if (label) return limpiar(label.textContent);

        // Encabezado de la tabla según la posición de la celda.
        const celda = campo.closest('td, th');
        const tabla = campo.closest('table');
        if (celda && tabla) {
            const encabezado = tabla.querySelectorAll('thead th')[celda.cellIndex];
            if (encabezado) return limpiar(encabezado.textContent);
        }

        return limpiar(campo.placeholder || campo.name);
    }

    function formatearFecha(valor) {
        const partes = /^(\d{4})-(\d{2})-(\d{2})$/.exec(valor);
        return partes ? `${partes[3]}/${partes[2]}/${partes[1]}` : valor;
    }

    function esOpcionDeRelleno(opcion) {
        const texto = limpiar(opcion.textContent).toLowerCase();
        return opcion.disabled || texto.startsWith('--') || texto.startsWith('selecc');
    }

    // Devuelve el texto que verá el usuario, o null si el campo no aporta nada.
    function valorDe(campo) {
        const tipo = (campo.type || '').toLowerCase();

        if (campo.tagName === 'SELECT') {
            const opcion = campo.options[campo.selectedIndex];
            if (!opcion || (!opcion.value && esOpcionDeRelleno(opcion))) return null;
            return limpiar(opcion.textContent);
        }

        if (tipo === 'checkbox') return campo.checked ? 'Sí' : 'No';
        if (tipo === 'radio') return campo.checked ? limpiar(campo.labels?.[0]?.textContent || campo.value) : null;

        if (tipo === 'file') {
            const nombres = Array.from(campo.files || []).map(archivo => archivo.name);
            return nombres.length ? nombres.join(', ') : null;
        }

        const valor = limpiar(campo.value);
        if (!valor) return null;

        return tipo === 'date' ? formatearFecha(valor) : valor;
    }

    function esCampoResumible(campo) {
        if (!(campo instanceof HTMLElement)) return false;
        if (['INPUT', 'SELECT', 'TEXTAREA'].indexOf(campo.tagName) === -1) return false;

        const tipo = (campo.type || '').toLowerCase();
        if (['hidden', 'submit', 'button', 'reset', 'image'].indexOf(tipo) !== -1) return false;
        if (campo.name === '_token' || campo.name === '_method') return false;
        if (campo.disabled || excluido(campo)) return false;
        if (tipo === 'radio' && !campo.checked) return false;

        return estaVisible(campo);
    }

    // ---------- Construcción del resumen ----------

    function leerFormulario(formulario) {
        const datos = [];
        const grupos = [];
        const totales = [];

        // Datos fijos enviados desde la vista.
        if (formulario.dataset.confirmDatos) {
            try {
                const fijos = JSON.parse(formulario.dataset.confirmDatos);
                Object.keys(fijos).forEach(function (etiqueta) {
                    const valor = limpiar(fijos[etiqueta]);
                    if (valor) datos.push([etiqueta, valor]);
                });
            } catch (error) {
                console.warn('data-confirm-datos no es un JSON válido.', error);
            }
        }

        if (formulario.dataset.confirmResumen === 'no') {
            return { datos, grupos, totales };
        }

        // Campos sueltos (los que están dentro de filas se leen aparte).
        Array.from(formulario.elements).forEach(function (campo) {
            if (!esCampoResumible(campo) || campo.closest('[data-resumen-fila]')) return;

            const valor = valorDe(campo);
            if (valor !== null) datos.push([etiquetaDe(campo), valor]);
        });

        // Bloques con filas repetidas.
        formulario.querySelectorAll('[data-resumen-grupo]').forEach(function (grupo) {
            if (excluido(grupo)) return;

            const columnas = [];
            const filas = [];

            grupo.querySelectorAll('[data-resumen-fila]').forEach(function (fila) {
                const celdas = {};
                let tieneDatos = false;

                fila.querySelectorAll('input, select, textarea, [data-resumen-valor]').forEach(function (elemento) {
                    let etiqueta;
                    let valor;

                    if (elemento.hasAttribute('data-resumen-valor')) {
                        if (excluido(elemento)) return;
                        etiqueta = limpiar(elemento.dataset.resumenValor);
                        valor = limpiar(elemento.textContent) || null;
                    } else {
                        if (!esCampoResumible(elemento)) return;
                        etiqueta = etiquetaDe(elemento);
                        valor = valorDe(elemento);
                        if (valor !== null) tieneDatos = true;
                    }

                    if (columnas.indexOf(etiqueta) === -1) columnas.push(etiqueta);
                    if (valor !== null) celdas[etiqueta] = valor;
                });

                if (tieneDatos) filas.push(celdas);
            });

            if (filas.length) {
                grupos.push({ titulo: limpiar(grupo.dataset.resumenGrupo), columnas, filas });
            }
        });

        // Valores calculados fuera de las filas (subtotal, IGV, total...).
        formulario.querySelectorAll('[data-resumen-valor]').forEach(function (elemento) {
            if (elemento.closest('[data-resumen-fila]') || excluido(elemento) || !estaVisible(elemento)) return;

            const valor = limpiar(elemento.textContent);
            if (valor) totales.push([limpiar(elemento.dataset.resumenValor), valor]);
        });

        return { datos, grupos, totales };
    }

    function crear(etiqueta, clase, texto) {
        const elemento = document.createElement(etiqueta);
        if (clase) elemento.className = clase;
        if (texto !== undefined) elemento.textContent = texto;
        return elemento;
    }

    function listaDeDatos(pares, clase) {
        const lista = crear('dl', 'confirmacion-datos ' + (clase || ''));

        pares.forEach(function (par) {
            lista.append(
                crear('dt', '', par[0]),
                crear('dd', '', recortar(par[1]))
            );
        });

        return lista;
    }

    function tablaDeGrupo(grupo) {
        const bloque = crear('div', 'confirmacion-grupo');
        bloque.append(crear('h6', 'confirmacion-subtitulo', `${grupo.titulo} (${grupo.filas.length})`));

        const contenedor = crear('div', 'table-responsive');
        const tabla = crear('table', 'table table-sm table-bordered mb-0 confirmacion-tabla');
        const thead = crear('thead');
        const cabecera = crear('tr');

        cabecera.append(crear('th', '', '#'));
        grupo.columnas.forEach(columna => cabecera.append(crear('th', '', columna)));
        thead.append(cabecera);
        tabla.append(thead);

        const cuerpo = crear('tbody');
        grupo.filas.forEach(function (fila, indice) {
            const tr = crear('tr');
            tr.append(crear('td', 'text-muted', String(indice + 1)));
            grupo.columnas.forEach(columna => tr.append(crear('td', '', recortar(fila[columna] ?? '—'))));
            cuerpo.append(tr);
        });

        tabla.append(cuerpo);
        contenedor.append(tabla);
        bloque.append(contenedor);

        return bloque;
    }

    function mostrarResumen(formulario) {
        const { datos, grupos, totales } = leerFormulario(formulario);

        resumen.replaceChildren();

        if (datos.length) resumen.append(listaDeDatos(datos));
        grupos.forEach(grupo => resumen.append(tablaDeGrupo(grupo)));
        if (totales.length) resumen.append(listaDeDatos(totales, 'confirmacion-totales'));

        resumen.hidden = !resumen.hasChildNodes();

        // Ventana más ancha cuando hay tablas.
        dialogo.classList.toggle('modal-lg', grupos.length > 0);
    }

    // ---------- Flujo de confirmación ----------

    document.addEventListener('submit', function (evento) {
        const formulario = evento.target;

        if (!formulario.matches('form[data-confirm]')) return;
        if (formulario.dataset.confirmado === 'true') return;

        evento.preventDefault();

        formularioPendiente = formulario;
        botonPendiente = evento.submitter || null;

        titulo.textContent = formulario.dataset.confirmTitulo || 'Confirmar acción';
        mensaje.textContent = formulario.dataset.confirm;
        btnAceptar.textContent = formulario.dataset.confirmBoton || 'Confirmar';

        mostrarResumen(formulario);
        modal.show();
    });

    btnAceptar.addEventListener('click', function () {
        if (!formularioPendiente) return;

        const formulario = formularioPendiente;
        const boton = botonPendiente;

        formularioPendiente = null;
        botonPendiente = null;
        formulario.dataset.confirmado = 'true';

        btnAceptar.disabled = true;
        modal.hide();

        // Conserva el botón que se pulsó, por si envía su propio name/value.
        if (boton && boton.form === formulario) {
            formulario.requestSubmit(boton);
        } else {
            formulario.requestSubmit();
        }
    });

    modalElemento.addEventListener('shown.bs.modal', function () {
        btnAceptar.disabled = false;
        btnAceptar.focus();
    });

    modalElemento.addEventListener('hidden.bs.modal', function () {
        formularioPendiente = null;
        botonPendiente = null;
    });

    // Al volver con el botón "Atrás", el formulario debe pedir confirmación otra vez.
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('form[data-confirmado]').forEach(function (formulario) {
            delete formulario.dataset.confirmado;
        });
        btnAceptar.disabled = false;
    });
});
