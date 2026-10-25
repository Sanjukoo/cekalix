<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ImportacionController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\MermaController;
use App\Http\Controllers\ConteoFisicoController;

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

    // Proveedores y apertura de importaciones: solo administrador.
    Route::middleware('role:admin')->group(function () {

        Route::resource('proveedores', ProveedorController::class)
            ->except(['show'])
            ->parameters(['proveedores' => 'proveedor']);

        Route::resource('importaciones', ImportacionController::class)
            ->only(['create', 'store'])
            ->parameters(['importaciones' => 'importacion']);
    });

    // Consulta de importaciones y conteo físico durante la descarga.
    Route::middleware('role:admin,inventario')->group(function () {
        Route::resource('importaciones', ImportacionController::class)
            ->only(['index', 'show'])
            ->parameters(['importaciones' => 'importacion']);

        Route::patch('/importaciones/{importacion}/conteo', [ConteoFisicoController::class, 'update'])
            ->name('importaciones.conteo.update');

        Route::patch('/importaciones/{importacion}/cerrar', [ImportacionController::class, 'cerrar'])
            ->name('importaciones.cerrar');
    });

    
    // Registro y consulta de mermas: admin e inventario.
    Route::middleware('role:admin,inventario')->group(function () {

        Route::get('/mermas', [MermaController::class, 'index'])
            ->name('mermas.index');

        Route::get('/mermas/crear', [MermaController::class, 'create'])
            ->name('mermas.create');

        Route::post('/mermas', [MermaController::class, 'store'])
            ->name('mermas.store');
    });

    // Edición y eliminación de mermas: solo administrador.
    Route::middleware('role:admin')->group(function () {

        Route::get('/mermas/{merma}/editar', [MermaController::class, 'edit'])
            ->name('mermas.edit');

        Route::put('/mermas/{merma}', [MermaController::class, 'update'])
            ->name('mermas.update');

        Route::delete('/mermas/{merma}', [MermaController::class, 'destroy'])
            ->name('mermas.destroy');
    });
});
