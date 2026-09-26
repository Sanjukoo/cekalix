<?php
// Variables para mostrar mensajes después de guardar (ver guardar.php)
$mensaje = "";
$tipoMensaje = "";

if (isset($_GET["ok"]) && $_GET["ok"] == "1") {
    $mensaje = "Producto registrado correctamente.";
    $tipoMensaje = "success";
} elseif (isset($_GET["error"]) && $_GET["error"] == "1") {
    $mensaje = "Ocurrió un error al registrar el producto.";
    $tipoMensaje = "danger";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Registro de Productos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Estilos propios -->
    <link href="assets/css/estilo.css" rel="stylesheet">
</head>
<body>

    <nav class="navbar navbar-custom mb-4">
        <div class="container">
            <span class="navbar-brand">Sistema de Registro de Productos</span>
        </div>
    </nav>

    <div class="container mb-5">

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="m-0">Nuevo producto</h4>
            <button type="button" class="btn btn-negro" data-bs-toggle="modal" data-bs-target="#modalProductos" id="btnVerProductos">
                Ver productos registrados
            </button>
        </div>

        <div class="card card-custom">
            <div class="card-header">Datos del producto</div>
            <div class="card-body">

                <form action="guardar.php" method="POST">

                    <!-- Datos generales, comunes a todas las categorías -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Código</label>
                            <input type="text" class="form-control" name="codigo" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nombre del producto</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nombre del proveedor</label>
                            <input type="text" class="form-control" name="proveedor" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stock</label>
                            <input type="number" class="form-control" name="stock" min="1" step="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Unidades por caja</label>
                            <input type="number" class="form-control" name="unidad_caja" min="1" step="1" required>
                        </div>

                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Categoría</label>
                            <select class="form-select" name="categoria" id="categoria" required>
                                <option value="">-- Selecciona una categoría --</option>
                                <option value="correderas">Correderas</option>
                                <option value="bisagras">Bisagras</option>
                                <option value="pistones">Pistones</option>
                                <option value="cerraduras">Cerradura</option>
                            </select>
                        </div>
                    </div>

                    <hr>

                    <!-- ===================== CORREDERAS ===================== -->
                    <div class="bloque-atributos" id="atributos-correderas" style="display:none;">
                        <h6 class="text-danger">Atributos de Correderas</h6>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Longitud</label>
                                <input type="text" class="form-control" name="longitud" data-requerido="true">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Espesor</label>
                                <input type="text" class="form-control" name="espesor" data-requerido="true">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Ancho</label>
                                <input type="text" class="form-control" name="ancho" data-requerido="true">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Color</label>
                                <input type="text" class="form-control" name="color" data-requerido="true">
                            </div>
                        </div>
                    </div>

                    <!-- ===================== BISAGRAS ===================== -->
                    <div class="bloque-atributos" id="atributos-bisagras" style="display:none;">
                        <h6 class="text-danger">Atributos de Bisagras</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Tipo</label>
                                <input type="text" class="form-control" name="tipo" data-requerido="true">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Acabado</label>
                                <input type="text" class="form-control" name="acabado" data-requerido="true">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Peso</label>
                                <input type="text" class="form-control" name="peso" data-requerido="true">
                            </div>
                        </div>
                    </div>

                    <!-- ===================== PISTONES ===================== -->
                    <div class="bloque-atributos" id="atributos-pistones" style="display:none;">
                        <h6 class="text-danger">Atributos de Pistones</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Fuerza</label>
                                <input type="text" class="form-control" name="fuerza" data-requerido="true">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Longitud</label>
                                <input type="text" class="form-control" name="longitud_piston" data-requerido="true">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Acabado</label>
                                <input type="text" class="form-control" name="acabado_piston" data-requerido="true">
                            </div>
                        </div>
                    </div>

                    <!-- ===================== CERRADURA ===================== -->
                    <div class="bloque-atributos" id="atributos-cerraduras" style="display:none;">
                        <h6 class="text-danger">Atributos de Cerradura</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Material</label>
                                <input type="text" class="form-control" name="material" data-requerido="true">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tamaño</label>
                                <input type="text" class="form-control" name="tamano" data-requerido="true">
                            </div>
                        </div>
                    </div>

                    <!-- Descripción, común a todas las categorías -->
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="3"></textarea>
                    </div>

                    <button type="submit" class="btn btn-rojo">Registrar producto</button>
                </form>

            </div>
        </div>
    </div>

    <!-- Modal: productos registrados -->
    <div class="modal fade" id="modalProductos" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Productos registrados</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="contenidoModalProductos">
                    <p class="text-center text-muted">Presiona el botón para cargar los productos.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Script propio -->
    <script src="assets/js/formulario.js"></script>
</body>
</html>
