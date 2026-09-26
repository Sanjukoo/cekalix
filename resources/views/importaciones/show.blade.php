@extends('layouts.app')

@section('title', 'Ver Importación')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h2 style="color: #000000;">Detalles de Importación #{{ $importacion->id }}</h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('importaciones.index') }}" class="btn btn-secondary">Volver</a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card card-custom mb-3">
            <div class="card-header">Información General</div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><strong>Proveedor</strong></label>
                        <p class="form-control-plaintext">{{ $importacion->proveedor->nombre }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><strong>Estado</strong></label>
                        <p class="form-control-plaintext">
                            @if($importacion->estado === 'En recepción')
                                <span class="badge bg-warning">{{ $importacion->estado }}</span>
                            @else
                                <span class="badge bg-success">{{ $importacion->estado }}</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><strong>Nº de Factura</strong></label>
                        <p class="form-control-plaintext">{{ $importacion->numero_factura }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><strong>Nº de Contenedor</strong></label>
                        <p class="form-control-plaintext">{{ $importacion->numero_contenedor }}</p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label"><strong>Fecha de Llegada</strong></label>
                        <p class="form-control-plaintext">{{ $importacion->fecha_llegada->format('d/m/Y') }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><strong>Registrado</strong></label>
                        <p class="form-control-plaintext">{{ $importacion->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-custom">
            <div class="card-header">Detalle de Mercadería</div>
            <div class="card-body">
                @if($importacion->detalles->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th>Cajas Facturadas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($importacion->detalles as $detalle)
                                    <tr>
                                        <td><span class="badge-sku">{{ $detalle->producto->codigo }}</span></td>
                                        <td>{{ $detalle->producto->nombre }}</td>
                                        <td>{{ $detalle->producto->categoria->nombre }}</td>
                                        <td><strong>{{ $detalle->cajas_facturadas }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center text-muted">Sin detalle de mercadería.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-custom bg-light">
            <div class="card-header">Datos de Auditoría</div>
            <div class="card-body small">
                <p><strong>Creado:</strong><br>{{ $importacion->created_at->format('d/m/Y H:i') }}</p>
                <p><strong>Actualizado:</strong><br>{{ $importacion->updated_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
