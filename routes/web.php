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

        // Registro y edición de productos: inventario.
    Route::middleware('role:inventario')->group(function () {
        Route::resource('productos', ProductoController::class)
            ->only(['create', 'store', 'edit', 'update']);

        Route::get('/categorias/nueva', [
            CategoriaController::class,
            'create',
        ])->name('categorias.create');

        Route::post('/categorias', [
            CategoriaController::class,
            'store',
        ])->name('categorias.store');
    });

    // Catálogo y stock: inventario y ventas.
    Route::middleware('role:inventario,ventas')->group(function () {
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
        Route::resource('proveedores', ProveedorController::class);

        Route::resource('importaciones', ImportacionController::class)
            ->only(['index', 'create', 'store', 'show']);
    });
});

    /*
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
    */