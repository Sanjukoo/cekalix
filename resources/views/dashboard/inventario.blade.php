@extends('layouts.app')

@section('title', 'Inventario')

@section('content')
    <div class="mb-4">
        <h1 class="page-title">Panel de inventario</h1>

        <p class="text-muted">
            Bienvenido, {{ auth()->user()->name }}.
            Consulta el stock y administra las fichas de productos.
        </p>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div class="card card-custom h-100">
                <div class="card-body">
                    <h2 class="h5">Productos registrados</h2>

                    <p class="stat-number">
                        {{ $totalProductos }}
                    </p>

                    <a href="{{ route('productos.index') }}"
                       class="btn btn-negro">
                        Ver catálogo y stock
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card card-custom h-100">
                <div class="card-body">
                    <h2 class="h5">Productos con stock bajo</h2>

                    <p class="stat-number">
                        {{ $productosBajoStock }}
                    </p>

                    <p class="text-muted">
                        Productos cuyo stock es menor a 10,
                        según el criterio actual del sistema.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('productos.create') }}"
           class="btn btn-rojo">
            Registrar producto
        </a>
    </div>
@endsection