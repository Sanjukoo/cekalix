@extends('layouts.app')

@section('title', 'Mi panel')

@section('content')
<div class="mb-4">
    <h1 class="page-title">Hola, {{ Auth::user()->name }}</h1>
    <p class="text-muted mb-0">Resumen de ventas.</p>
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
        'icono' => 'bi-receipt',
        'titulo' => 'Operaciones de hoy',
        'valor' => $ventasHoyCantidad,
    ])

    @include('dashboard._indicador', [
        'icono' => 'bi-graph-up-arrow',
        'titulo' => 'Ventas del mes',
        'valor' => 'S/ '.number_format($ventasMes, 2),
    ])

    @include('dashboard._indicador', [
        'icono' => 'bi-people',
        'titulo' => 'Clientes registrados',
        'valor' => $totalClientes,
        'enlace' => route('clientes.index'),
        'textoEnlace' => 'Ver clientes',
    ])
</div>

<h2 class="h6 text-uppercase text-muted fw-bold mb-3">Accesos rápidos</h2>

<div class="row g-3">
    <div class="col-md-6">
        <a href="{{ route('ventas.create') }}" class="acceso">
            <span class="acceso-icono"><i class="bi bi-plus-lg"></i></span>
            <span><strong>Registrar venta</strong><small>Nueva salida de productos</small></span>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('clientes.create') }}" class="acceso">
            <span class="acceso-icono"><i class="bi bi-person-plus"></i></span>
            <span><strong>Nuevo cliente</strong><small>Registrar un cliente para las ventas</small></span>
        </a>
    </div>
</div>
@endsection
