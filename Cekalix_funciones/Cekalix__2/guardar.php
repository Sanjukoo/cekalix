
<?php
require_once "config/db.php";

// Solo procesamos si el formulario fue enviado por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

// Datos generales
$codigo      = trim($_POST["codigo"] ?? "");
$nombre      = trim($_POST["nombre"] ?? "");
$proveedor   = trim($_POST["proveedor"] ?? "");
$categoria   = trim($_POST["categoria"] ?? "");
$stock       = trim($_POST["stock"] ?? "");
$unidad_caja = trim($_POST["unidad_caja"] ?? "");
$descripcion = trim($_POST["descripcion"] ?? "");

// Atributos según categoría (todos inician vacíos)
$longitud = null;
$espesor  = null;
$ancho    = null;
$color    = null;
$tipo     = null;
$acabado  = null;
$peso     = null;
$fuerza   = null;
$material = null;
$tamano   = null;

switch ($categoria) {
    case "correderas":
        $longitud = trim($_POST["longitud"] ?? "");
        $espesor  = trim($_POST["espesor"] ?? "");
        $ancho    = trim($_POST["ancho"] ?? "");
        $color    = trim($_POST["color"] ?? "");
        break;

    case "bisagras":
        $tipo     = trim($_POST["tipo"] ?? "");
        $acabado  = trim($_POST["acabado"] ?? "");
        $peso     = trim($_POST["peso"] ?? "");
        break;

    case "pistones":
        $fuerza   = trim($_POST["fuerza"] ?? "");
        $longitud = trim($_POST["longitud_piston"] ?? "");
        $acabado  = trim($_POST["acabado_piston"] ?? "");
        break;

    case "cerraduras":
        $material = trim($_POST["material"] ?? "");
        $tamano   = trim($_POST["tamano"] ?? "");
        break;
}

// Validación mínima: campos generales obligatorios
if (
    $codigo === "" ||
    $nombre === "" ||
    $proveedor === "" ||
    $categoria === "" ||
    $stock === "" ||
    $unidad_caja === ""
) {
    header("Location: index.php?error=1");
    exit;
}

try {
    $sql = "INSERT INTO productos
            (codigo, nombre, proveedor, categoria, stock, unidad_caja, longitud, espesor, ancho, color,
             tipo, acabado, peso, fuerza, material, tamano, descripcion)
            VALUES
            (:codigo, :nombre, :proveedor, :categoria, :stock, :unidad_caja, :longitud, :espesor, :ancho, :color,
             :tipo, :acabado, :peso, :fuerza, :material, :tamano, :descripcion)";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":codigo"      => $codigo,
        ":nombre"      => $nombre,
        ":proveedor"   => $proveedor,
        ":categoria"   => $categoria,
        ":stock"       => $stock,
        ":unidad_caja" => $unidad_caja,
        ":longitud"    => $longitud,
        ":espesor"     => $espesor,
        ":ancho"       => $ancho,
        ":color"       => $color,
        ":tipo"        => $tipo,
        ":acabado"     => $acabado,
        ":peso"        => $peso,
        ":fuerza"      => $fuerza,
        ":material"    => $material,
        ":tamano"      => $tamano,
        ":descripcion" => $descripcion,
    ]);

    header("Location: index.php?ok=1");
    exit;

} catch (PDOException $e) {
    header("Location: index.php?error=1");
    exit;
}

