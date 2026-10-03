<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ImportacionController;
use App\Http\Controllers\CategoriaController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Primero: comprobar que el usuario inició sesión.
Route::middleware('auth')->group(function () {

    // Dashboard disponible para los tres roles.
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:admin,inventario,ventas')
        ->name('dashboard');

    // Registro, edición y eliminación de productos: admin e inventario.
    Route::middleware('role:admin,inventario')->group(function () {
        Route::resource('productos', ProductoController::class)
            ->only(['create', 'store', 'edit', 'update', 'destroy']);

        Route::get('/categorias/nueva', [
            CategoriaController::class,
            'create',
        ])->name('categorias.create');

        Route::post('/categorias', [
            CategoriaController::class,
            'store',
        ])->name('categorias.store');
    });

    // Catálogo y stock: los tres roles.
    Route::middleware('role:admin,inventario,ventas')->group(function () {
        Route::resource('productos', ProductoController::class)
            ->only(['index', 'show']);
    });

    // Selector de productos para las operaciones autorizadas.
    Route::get('/productos-ajax/listar', [
        ProductoController::class,
        'listarAjax',
    ])
        ->middleware('role:admin,inventario,ventas')
        ->name('productos.listar-ajax');

    // Proveedores e importaciones: administración.
    Route::middleware('role:admin')->group(function () {
        // Laravel singulariza en inglés ({proveedore}); se fija el nombre
        // para que coincida con el parámetro del controlador.
        Route::resource('proveedores', ProveedorController::class)
            ->except(['show'])
            ->parameters(['proveedores' => 'proveedor']);

        Route::resource('importaciones', ImportacionController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->parameters(['importaciones' => 'importacion']);
    });
});