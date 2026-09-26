<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | CEKALIX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <span class="navbar-brand mb-0 h1">CEKALIX</span>
        <a href="auth/logout.php" class="btn btn-outline-light">Cerrar sesión</a>
    </div>
</nav>

<div class="container mt-5">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h2 class="mb-3">Bienvenido, <?= htmlspecialchars($_SESSION["nombre"]) ?></h2>
            <p class="mb-1">Correo: <strong><?= htmlspecialchars($_SESSION["correo"]) ?></strong></p>
            <p>Rol: <strong><?= htmlspecialchars($_SESSION["rol"]) ?></strong></p>
            <div class="alert alert-success mb-0">Inicio de sesión realizado correctamente.</div>
        </div>
    </div>
</div>
</body>
</html>
