<?php
// =====================================================
// Conexión a la base de datos con PDO
// Ajusta estos datos solo si tu configuración de XAMPP
// es distinta a la de por defecto (usuario root, sin clave)
// =====================================================

$host   = "localhost";
$dbname = "sistema_productos";
$user   = "root";
$pass   = "";

try {
    $conexion = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass
    );
    // Para que PDO avise con errores claros si algo falla
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
