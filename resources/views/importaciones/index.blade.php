@extends('layouts.app')

@section('title', 'Importaciones')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="page-title">Importaciones Registradas</h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('importaciones.create') }}" class="btn btn-rojo">
            Nueva Importación
        </a>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body">
        @if($importaciones->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Proveedor</th>
                            <th>Factura / Contenedor</th>
                            <th>Fecha Llegada</th>
                            <th>Producto (SKU)</th>
                            <th>Cajas</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($importaciones as $importacion)
                            @foreach($importacion->detalles as $detalle)
                                <tr>
                                    <td><strong>#{{ $importacion->id }}</strong></td>
                                    <td>{{ $importacion->proveedor->razon_social }}</td>
                                    <td>
                                        <small class="d-block">Fac: {{ $importacion->numero_factura }}</small>
                                        <small class="text-muted">Cont: {{ $importacion->numero_contenedor }}</small>
                                    </td>
                                    <td>{{ $importacion->fecha_llegada->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="badge-sku">{{ $detalle->producto->codigo }}</span>
                                        <small class="d-block">{{ $detalle->producto->nombre }}</small>
                                    </td>
                                    <td><strong>{{ $detalle->cajas_facturadas }}</strong></td>
                                    <td>
                                        @if($importacion->estado === 'En recepción')
                                            <span class="badge badge-estado en-recepcion">{{ $importacion->estado }}</span>
                                        @else
                                            <span class="badge badge-estado procesada">{{ $importacion->estado }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('importaciones.show', $importacion) }}" class="btn btn-sm btn-outline-dark" title="Ver">Ver</a>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $importaciones->links() }}
            </div>
        @else
            <p class="text-center text-muted py-4">No hay importaciones registradas.</p>
        @endif
    </div>
</div>
@endsection
