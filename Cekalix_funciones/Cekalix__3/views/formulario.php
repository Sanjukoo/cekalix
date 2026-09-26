<div class="card card-sistema">
    <div class="card-header">Datos de la importación (CP-003)</div>
    <div class="card-body">

        <?php if ($alerta): ?>
            <div class="alert alert-<?= e($alerta['tipo']); ?> alert-dismissible fade show" role="alert">
                <?= $alerta['mensaje']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <form action="index.php" method="POST" novalidate>
            <h6 class="seccion-titulo">Datos generales</h6>

            <div class="mb-3">
                <label for="proveedor_id" class="form-label">Proveedor <span class="text-danger">*</span></label>
                <select class="form-select" id="proveedor_id" name="proveedor_id" required>
                    <option value="">-- Seleccionar proveedor registrado --</option>
                    <?php foreach ($proveedores as $id => $nombre): ?>
                        <option value="<?= e($id); ?>"
                            <?= (($formData['proveedor_id'] ?? '') == $id) ? 'selected' : ''; ?>>
                            <?= e($nombre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="num_factura" class="form-label">N.º de Factura <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="num_factura" name="num_factura"
                           placeholder="Ej: FAC-00125"
                           value="<?= e($formData['num_factura'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="num_contenedor" class="form-label">N.º de Contenedor <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="num_contenedor" name="num_contenedor"
                           placeholder="Ej: CONT-001"
                           value="<?= e($formData['num_contenedor'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="fecha_llegada" class="form-label">Fecha de Llegada <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="fecha_llegada" name="fecha_llegada"
                       value="<?= e($formData['fecha_llegada'] ?? ''); ?>" required>
            </div>

            <h6 class="seccion-titulo mt-3">Detalle de mercadería</h6>

            <div class="row">
                <div class="col-md-7 mb-3">
                    <label for="producto_id" class="form-label">SKU / Producto <span class="text-danger">*</span></label>
                    <select class="form-select" id="producto_id" name="producto_id" required>
                        <option value="">-- Seleccionar SKU existente --</option>
                        <?php foreach ($productos as $prod): ?>
                            <option value="<?= e($prod['id']); ?>"
                                <?= (($formData['producto_id'] ?? '') == $prod['id']) ? 'selected' : ''; ?>>
                                <?= e($prod['codigo'] . ' - ' . $prod['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5 mb-3">
                    <label for="cajas" class="form-label">Cajas Facturadas <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="cajas" name="cajas" min="1"
                           placeholder="Ej: 50"
                           value="<?= e($formData['cajas'] ?? ''); ?>" required>
                </div>
            </div>

            <button type="submit" class="btn btn-rojo mt-2">Registrar importación</button>
        </form>

    </div>
</div>
