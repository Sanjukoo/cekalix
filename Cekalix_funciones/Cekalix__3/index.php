<?php
// ----------------------------------------------------
// CP-003 - Registrar llegada de una importación
// Controlador principal: procesa el formulario y carga las vistas
// ----------------------------------------------------
session_start();
require_once __DIR__ . '/includes/funciones.php';

// 1. PROCESAMIENTO DEL FORMULARIO (POST + REDIRECT)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'proveedor_id'   => trim($_POST['proveedor_id'] ?? ''),
        'num_factura'    => trim($_POST['num_factura'] ?? ''),
        'num_contenedor' => trim($_POST['num_contenedor'] ?? ''),
        'fecha_llegada'  => trim($_POST['fecha_llegada'] ?? ''),
        'producto_id'    => trim($_POST['producto_id'] ?? ''),
        'cajas'          => trim($_POST['cajas'] ?? ''),
    ];

    // Guardar temporalmente los datos para no perderlos si ocurre un error
    $_SESSION['form_data'] = $datos;

    $error = validarImportacion($datos);

    if ($error) {
        $_SESSION['flash_alerta'] = ['tipo' => 'danger', 'mensaje' => $error];
    } else {
        try {
            $folio = registrarImportacion($datos);
            unset($_SESSION['form_data']);
            $_SESSION['flash_alerta'] = [
                'tipo'    => 'success',
                'mensaje' => "La importación fue registrada correctamente con estado <strong>En recepción</strong> (Folio #$folio)."
            ];
        } catch (PDOException $e) {
            $_SESSION['flash_alerta'] = [
                'tipo'    => 'danger',
                'mensaje' => 'No se pudo registrar la importación. Intente nuevamente.'
            ];
        }
    }

    header('Location: index.php');
    exit();
}

// 2. RECUPERAR MENSAJES Y DATOS TEMPORALES
$alerta = $_SESSION['flash_alerta'] ?? null;
unset($_SESSION['flash_alerta']);

$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

// 3. CONSULTAS A LA BASE DE DATOS
$proveedores   = obtenerProveedores();
$productos     = obtenerProductos();
$importaciones = obtenerImportaciones();

// 4. VISTAS
require __DIR__ . '/views/header.php';
require __DIR__ . '/views/formulario.php';
require __DIR__ . '/views/tabla_importaciones.php';
require __DIR__ . '/views/footer.php';
