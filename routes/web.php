<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ImportacionController;

// Rutas públicas de autenticación
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');

// Rutas protegidas por autenticación
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Productos
    Route::resource('productos', ProductoController::class);
    Route::get('/productos-ajax/listar', [ProductoController::class, 'listarAjax'])->name('productos.listar-ajax');

    // Proveedores
    Route::resource('proveedores', ProveedorController::class);

    // Importaciones
    Route::resource('importaciones', ImportacionController::class)->only(['index', 'create', 'store', 'show']);
});
