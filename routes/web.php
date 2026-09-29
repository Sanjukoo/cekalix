<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ImportacionController;
use App\Http\Controllers\CategoriaController;

// Las rutas /login y /logout las registra Laravel Fortify (config/fortify.php)
Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas protegidas por autenticación
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Productos
    Route::resource('productos', ProductoController::class);
    Route::get('/productos-ajax/listar', [ProductoController::class, 'listarAjax'])->name('productos.listar-ajax');

    // Categorías
    Route::get('/categorias/nueva', [CategoriaController::class, 'create'])->name('categorias.create');
    Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');

    // Proveedores
    Route::resource('proveedores', ProveedorController::class);

    // Importaciones
    Route::resource('importaciones', ImportacionController::class)->only(['index', 'create', 'store', 'show']);
});
