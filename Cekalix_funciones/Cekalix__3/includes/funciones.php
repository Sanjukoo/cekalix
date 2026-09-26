<?php
// ----------------------------------------------------
// FUNCIONES DE ACCESO A DATOS (consultas MySQL)
// ----------------------------------------------------
require_once __DIR__ . '/../config/database.php';

function obtenerProveedores(): array
{
    $sql = "SELECT id, nombre FROM proveedores WHERE activo = 1 ORDER BY nombre";
    return conectar()->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR); // [id => nombre]
}

function obtenerProductos(): array
{
    $sql = "SELECT id, codigo, nombre FROM productos ORDER BY codigo";
    return conectar()->query($sql)->fetchAll();
}

function existeProveedor($id): bool
{
    $stmt = conectar()->prepare("SELECT 1 FROM proveedores WHERE id = ? AND activo = 1");
    $stmt->execute([$id]);
    return (bool) $stmt->fetchColumn();
}

function existeProducto($productoId): bool
{
    $stmt = conectar()->prepare("SELECT 1 FROM productos WHERE id = ?");
    $stmt->execute([$productoId]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Registra cabecera + detalle en una transacción.
 * Si falla uno, no se guarda ninguno (evita el BUG-003).
 * Devuelve el ID (folio) de la importación.
 */
function registrarImportacion(array $datos): int
{
    $pdo = conectar();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "INSERT INTO importaciones (proveedor_id, num_factura, num_contenedor, fecha_llegada, estado)
             VALUES (?, ?, ?, ?, 'En recepción')"
        );
        $stmt->execute([
            $datos['proveedor_id'],
            $datos['num_factura'],
            $datos['num_contenedor'],
            $datos['fecha_llegada'],
        ]);

        $importacionId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            "INSERT INTO importacion_detalle (importacion_id, producto_id, cajas_facturadas)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$importacionId, $datos['producto_id'], (int) $datos['cajas']]);

        $pdo->commit();
        return $importacionId;
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function obtenerImportaciones(): array
{
    $sql = "SELECT i.id, p.nombre AS proveedor_nom, i.num_factura, i.num_contenedor,
                   i.fecha_llegada, i.estado, i.fecha_registro,
                   pr.codigo AS sku, pr.nombre AS producto_nom, d.cajas_facturadas
            FROM importaciones i
            INNER JOIN proveedores p          ON p.id = i.proveedor_id
            LEFT JOIN importacion_detalle d   ON d.importacion_id = i.id
            LEFT JOIN productos pr            ON pr.id = d.producto_id
            ORDER BY i.id DESC";
    return conectar()->query($sql)->fetchAll();
}

/**
 * Valida los datos del formulario. Devuelve el mensaje de error o null si todo está bien.
 */
function validarImportacion(array $d): ?string
{
    foreach (['proveedor_id', 'num_factura', 'num_contenedor', 'fecha_llegada', 'producto_id', 'cajas'] as $campo) {
        if ($d[$campo] === '') {
            return 'Todos los campos marcados como obligatorios deben ser completados.';
        }
    }
    if (!existeProveedor($d['proveedor_id'])) {
        return 'El proveedor seleccionado no existe en el registro del sistema.';
    }
    if (!existeProducto($d['producto_id'])) {
        return 'El producto (SKU) seleccionado no existe previamente en el catálogo.';
    }
    if ((int) $d['cajas'] <= 0) {
        return 'La cantidad de cajas facturadas debe ser mayor a 0.';
    }
    return null;
}

function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
