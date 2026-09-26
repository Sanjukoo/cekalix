// =====================================================
// Muestra los campos de atributos según la categoría
// que el usuario seleccione en el <select>
// =====================================================

document.addEventListener("DOMContentLoaded", function () {
    const selectCategoria = document.getElementById("categoria");
    const bloques = document.querySelectorAll(".bloque-atributos");

    function mostrarBloque() {
        const categoria = selectCategoria.value;

        // Ocultamos todos los bloques y quitamos el "required" de sus campos
        bloques.forEach(function (bloque) {
            bloque.style.display = "none";
            bloque.querySelectorAll("input, select").forEach(function (campo) {
                campo.required = false;
            });
        });

        // Mostramos solo el bloque de la categoría elegida
        if (categoria) {
            const bloqueActivo = document.getElementById("atributos-" + categoria);
            if (bloqueActivo) {
                bloqueActivo.style.display = "block";
                bloqueActivo.querySelectorAll("input, select").forEach(function (campo) {
                    if (campo.dataset.requerido === "true") {
                        campo.required = true;
                    }
                });
            }
        }
    }

    selectCategoria.addEventListener("change", mostrarBloque);
    mostrarBloque(); // por si la página se recarga con un valor ya elegido

    // =====================================================
    // Botón "Ver productos registrados": carga la tabla
    // dentro del modal usando fetch (AJAX) sin recargar la página
    // =====================================================
    const btnVerProductos = document.getElementById("btnVerProductos");
    const contenidoModal = document.getElementById("contenidoModalProductos");

    if (btnVerProductos) {
        btnVerProductos.addEventListener("click", function () {
            contenidoModal.innerHTML = "<p class='text-center'>Cargando...</p>";

            fetch("listar.php")
                .then(function (respuesta) {
                    return respuesta.text();
                })
                .then(function (html) {
                    contenidoModal.innerHTML = html;
                })
                .catch(function () {
                    contenidoModal.innerHTML =
                        "<p class='text-danger text-center'>No se pudieron cargar los productos.</p>";
                });
        });
    }
});
