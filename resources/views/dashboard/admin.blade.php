@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2 class="page-title">Bienvenido, {{ Auth::user()->name }}</h2>
        <p class="text-muted">Panel de Control</p>
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card card-custom">
            <div class="card-body text-center">
                <h4 class="stat-number">{{ $totalProductos }}</h4>
                <p class="text-muted mb-0">Productos Registrados</p>
                <a href="{{ route('productos.index') }}" class="btn btn-sm btn-outline-dark mt-2">Ver productos</a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card card-custom">
            <div class="card-body text-center">
                <h4 class="stat-number">{{ $totalProveedores }}</h4>
                <p class="text-muted mb-0">Proveedores Activos</p>
                <a href="{{ route('proveedores.index') }}" class="btn btn-sm btn-outline-dark mt-2">Ver proveedores</a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card card-custom">
            <div class="card-body text-center">
                <h4 class="stat-number">{{ $totalImportaciones }}</h4>
                <p class="text-muted mb-0">Importaciones Registradas</p>
                <a href="{{ route('importaciones.index') }}" class="btn btn-sm btn-outline-dark mt-2">Ver importaciones</a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card card-custom border-warning">
            <div class="card-body text-center">
                <h4 class="stat-number alerta">{{ $productosBajoStock }}</h4>
                <p class="text-muted mb-0">Productos Bajo Stock</p>
                <a href="{{ route('productos.index') }}" class="btn btn-sm btn-outline-rojo mt-2">Revisar stock</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">Acciones Rápidas</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <a href="{{ route('productos.create') }}" class="btn btn-rojo w-100">
                            Nuevo Producto
                        </a>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <a href="{{ route('proveedores.create') }}" class="btn btn-negro w-100">
                            Nuevo Proveedor
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('importaciones.create') }}" class="btn btn-outline-dark w-100">
                            Nueva Importación
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
