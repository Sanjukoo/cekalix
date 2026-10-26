@extends('layouts.app')

@section('title', 'Mi panel')

@section('content')
<div class="mb-4">
    <h1 class="page-title">Hola, {{ Auth::user()->name }}</h1>
    <p class="text-muted mb-0">Así está hoy tu negocio.</p>
</div>

<div class="row g-3 mb-4">
    @include('dashboard._indicador', [
        'icono' => 'bi-cash-coin',
        'titulo' => 'Ventas de hoy',
        'valor' => 'S/ '.number_format($ventasHoy, 2),
        'enlace' => route('ventas.index'),
        'textoEnlace' => 'Ver ventas',
    ])

    @include('dashboard._indicador', [
        'icono' => 'bi-graph-up-arrow',
        'titulo' => 'Ventas del mes',
        'valor' => 'S/ '.number_format($ventasMes, 2),
    ])

    @include('dashboard._indicador', [
        'icono' => 'bi-exclamation-triangle',
        'titulo' => 'Productos con stock bajo',
        'valor' => $stockBajo,
        'alerta' => $stockBajo > 0,
        'enlace' => route('productos.index'),
        'textoEnlace' => 'Revisar stock',
    ])

    @include('dashboard._indicador', [
        'icono' => 'bi-truck',
        'titulo' => 'Importaciones en recepción',
        'valor' => $enRecepcion,
        'enlace' => route('importaciones.index'),
        'textoEnlace' => 'Ver recepción',
    ])
</div>

<h2 class="h6 text-uppercase text-muted fw-bold mb-3">Accesos rápidos</h2>

<div class="row g-3">
    <div class="col-md-4">
        <a href="{{ route('ventas.create') }}" class="acceso">
            <span class="acceso-icono"><i class="bi bi-plus-lg"></i></span>
            <span><strong>Registrar venta</strong><small>Nueva salida de productos</small></span>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('productos.index') }}" class="acceso">
            <span class="acceso-icono"><i class="bi bi-box-seam"></i></span>
            <span><strong>Catálogo y stock</strong><small>Productos, precios e historial</small></span>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('proveedores.index') }}" class="acceso">
            <span class="acceso-icono"><i class="bi bi-people"></i></span>
            <span><strong>Socios</strong><small>Proveedores y clientes</small></span>
        </a>
    </div>
</div>
@endsection
