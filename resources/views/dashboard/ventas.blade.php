@extends('layouts.app')

@section('title', 'Ventas')

@section('content')
    <div class="mb-4">
        <h1 class="page-title">Panel de ventas</h1>

        <p class="text-muted">
            Bienvenido, {{ auth()->user()->name }}.
            Consulta los productos y su stock disponible.
        </p>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div class="card card-custom">
                <div class="card-body">
                    <h2 class="h5">Productos registrados</h2>

                    <p class="stat-number">
                        {{ $totalProductos }}
                    </p>

                    <a href="{{ route('productos.index') }}"
                       class="btn btn-negro">
                        Consultar catálogo y stock
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection