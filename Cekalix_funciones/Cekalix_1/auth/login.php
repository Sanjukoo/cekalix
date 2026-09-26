<?php
session_start();

if (isset($_SESSION['usuario_id'])) {
    header("Location: ../dashboard.php");
    exit;
}

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesión | CEKALIX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <h3 class="text-center mb-1">CEKALIX</h3>
                    <p class="text-center text-muted mb-4">Inicio de sesión</p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="procesar_login.php">
                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo</label>
                            <input type="email" id="correo" name="correo" class="form-control" required autocomplete="username">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Iniciar sesión</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
