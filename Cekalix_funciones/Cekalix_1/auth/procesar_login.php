<?php
session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

$correo = trim($_POST["correo"] ?? "");
$password = $_POST["password"] ?? "";

if ($correo === "" || $password === "") {
    $_SESSION["error"] = "Todos los campos son obligatorios.";
    header("Location: login.php");
    exit;
}

$sql = "SELECT id, correo, password, nombre, rol
        FROM usuarios
        WHERE correo = :correo
        LIMIT 1";

$stmt = $conexion->prepare($sql);
$stmt->execute(["correo" => $correo]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user["password"])) {
    $_SESSION["error"] = "Correo o contraseña incorrectos.";
    header("Location: login.php");
    exit;
}

session_regenerate_id(true);
$_SESSION["usuario_id"] = $user["id"];
$_SESSION["correo"] = $user["correo"];
$_SESSION["nombre"] = $user["nombre"];
$_SESSION["rol"] = $user["rol"];

header("Location: ../dashboard.php");
exit;
