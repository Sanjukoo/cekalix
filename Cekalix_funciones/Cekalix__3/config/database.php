<?php
// ----------------------------------------------------
// CONFIGURACIÓN DE LA CONEXIÓN A MySQL (PDO)
// Cambia estos datos según tu servidor (XAMPP, Laragon, etc.)
// ----------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'cekalix');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

date_default_timezone_set('America/Lima');

/**
 * Devuelve una única conexión PDO reutilizable.
 */
function conectar(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('Error de conexión a la base de datos: ' . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}
