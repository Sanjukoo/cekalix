@extends('layouts.app')

@section('title', 'Mi panel')

@section('content')
<div class="mb-4">
    <h1 class="page-title">Hola, {{ Auth::user()->name }}</h1>
    <p class="text-muted mb-0">Estado del inventario.</p>
</div>

<div class="row g-3 mb-4">
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

    @include('dashboard._indicador', [
        'icono' => 'bi-arrow-left-right',
        'titulo' => 'Movimientos de hoy',
        'valor' => $movimientosHoy,
    ])
</div>

<h2 class="h6 text-uppercase text-muted fw-bold mb-3">Accesos rápidos</h2>

<div class="row g-3">
    <div class="col-md-6">
        <a href="{{ route('productos.index') }}" class="acceso">
            <span class="acceso-icono"><i class="bi bi-box-seam"></i></span>
            <span><strong>Catálogo y stock</strong><small>Productos, ajustes e historial</small></span>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('importaciones.index') }}" class="acceso">
            <span class="acceso-icono"><i class="bi bi-truck"></i></span>
            <span><strong>Recepción</strong><small>Importaciones y mermas</small></span>
        </a>
    </div>
</div>
@endsection
