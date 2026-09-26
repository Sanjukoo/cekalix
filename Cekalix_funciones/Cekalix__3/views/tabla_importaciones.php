<!-- Tabla de verificación: comprueba que el detalle queda asociado (BUG-003) -->
<?php if (!empty($importaciones)): ?>
    <div class="card card-sistema" id="importaciones-registradas">
        <div class="card-header">Importaciones registradas</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover tabla-sistema">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Proveedor</th>
                            <th>Factura / Contenedor</th>
                            <th>Fecha Llegada</th>
                            <th>Detalle Asociado (SKU / Cajas)</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($importaciones as $imp): ?>
                            <tr>
                                <td><strong>#<?= e($imp['id']); ?></strong></td>
                                <td><?= e($imp['proveedor_nom']); ?></td>
                                <td>
                                    <small class="d-block">Fac: <?= e($imp['num_factura']); ?></small>
                                    <small class="text-muted">Cont: <?= e($imp['num_contenedor']); ?></small>
                                </td>
                                <td><?= e(date('d/m/Y', strtotime($imp['fecha_llegada']))); ?></td>
                                <td>
                                    <?php if ($imp['sku']): ?>
                                        <span class="badge badge-sku"><?= e($imp['sku']); ?></span>
                                        <span class="text-muted">(<?= e($imp['cajas_facturadas']); ?> cajas)</span>
                                    <?php else: ?>
                                        <span class="text-danger">Sin detalle</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-estado"><?= e($imp['estado']); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
