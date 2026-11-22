/*
 * Campos de archivo de CEKALIX.
 *
 * El navegador muestra junto al botón el texto "No se eligió ningún archivo",
 * que se corta en columnas estrechas ("No...ivo"). Aquí cada campo de archivo
 * se muestra como un único botón "Seleccionar archivo". El campo original
 * queda oculto a la vista pero sigue siendo el que se envía y el que recibe
 * el foco con el teclado. Al elegir un archivo, su nombre aparece debajo.
 */
document.addEventListener('DOMContentLoaded', function () {
    let contador = 0;

    document.querySelectorAll('input[type="file"]').forEach(function (campo) {
        if (campo.closest('.campo-archivo')) return;

        if (!campo.id) {
            contador += 1;
            campo.id = 'campo-archivo-' + contador;
        }

        const contenedor = document.createElement('div');
        contenedor.className = 'campo-archivo';
        if (campo.classList.contains('is-invalid')) contenedor.classList.add('is-invalid');
        if (campo.classList.contains('form-control-sm')) contenedor.classList.add('campo-archivo-sm');

        const boton = document.createElement('label');
        boton.className = 'campo-archivo-boton';
        boton.htmlFor = campo.id;
        boton.innerHTML = '<i class="bi bi-paperclip" aria-hidden="true"></i><span>Seleccionar archivo</span>';

        const nombre = document.createElement('span');
        nombre.className = 'campo-archivo-nombre';
        nombre.setAttribute('aria-live', 'polite');

        campo.parentNode.insertBefore(contenedor, campo);
        contenedor.append(campo, boton, nombre);
        campo.classList.add('campo-archivo-input');

        campo.addEventListener('change', function () {
            const archivos = Array.from(campo.files || []).map(archivo => archivo.name);
            const texto = boton.querySelector('span');

            if (archivos.length) {
                nombre.innerHTML = '';
                const icono = document.createElement('i');
                icono.className = 'bi bi-check-circle';
                icono.setAttribute('aria-hidden', 'true');
                nombre.append(icono, ' ' + archivos.join(', '));
                texto.textContent = 'Cambiar archivo';
            } else {
                nombre.textContent = '';
                texto.textContent = 'Seleccionar archivo';
            }
        });
    });
});
