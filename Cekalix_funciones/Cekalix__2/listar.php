<?php
require_once "config/db.php";

$stmt = $conexion->query("SELECT * FROM productos ORDER BY id DESC");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Etiquetas legibles para cada categoría
$nombresCategoria = [
    "correderas" => "Correderas",
    "bisagras"   => "Bisagras",
    "pistones"   => "Pistones",
    "cerraduras" => "Cerradura",
];

// Función para armar el texto de atributos según la categoría del producto
function textoAtributos($producto) {
    switch ($producto["categoria"]) {
        case "correderas":
            return "Longitud: {$producto['longitud']} | Espesor: {$producto['espesor']} | "
                 . "Ancho: {$producto['ancho']} | Color: {$producto['color']}";
        case "bisagras":
            return "Tipo: {$producto['tipo']} | Acabado: {$producto['acabado']} | "
                 . "Peso: {$producto['peso']}";
        case "pistones":
            return "Fuerza: {$producto['fuerza']} | Longitud: {$producto['longitud']} | "
                 . "Acabado: {$producto['acabado']}";
        case "cerraduras":
            return "Material: {$producto['material']} | Tamaño: {$producto['tamano']}";
        default:
            return "";
    }
}
?>

<?php if (count($productos) === 0): ?>
    <p class="text-center text-muted">Todavía no hay productos registrados.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Proveedor</th>
                    <th>Stock</th>
                    <th>Unidad_Caja</th>
                    <th>Categoría</th>
                    <th>Atributos</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($producto["codigo"]); ?></td>
                        <td><?php echo htmlspecialchars($producto["nombre"]); ?></td>
                        <td><?php echo htmlspecialchars($producto["proveedor"]); ?></td>
                        <td><?php echo htmlspecialchars($producto["stock"]); ?></td>
                        <td><?php echo htmlspecialchars($producto["unidad_caja"]); ?></td>
                        <td><?php echo htmlspecialchars($nombresCategoria[$producto["categoria"]] ?? $producto["categoria"]); ?></td>
                        <td><?php echo htmlspecialchars(textoAtributos($producto)); ?></td>
                        <td><?php echo htmlspecialchars($producto["descripcion"]); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
