@extends('layouts.app')

@section('title', 'Ver Importación')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="page-title">Detalles de Importación #{{ $importacion->id }}</h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('importaciones.index') }}" class="btn btn-outline-secondary">Volver</a>
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
                        <p class="form-control-plaintext">{{ $importacion->proveedor->razon_social }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><strong>Estado</strong></label>
                        <p class="form-control-plaintext">
                            @if($importacion->estado === 'En recepción')
                                <span class="badge badge-estado en-recepcion">{{ $importacion->estado }}</span>
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

        <div class="card card-custom mb-3">
            <div class="card-header">Documento de la Factura</div>
            <div class="card-body">
                @if($importacion->archivo_factura)
                    @php
                        $urlFactura = route('importaciones.factura', $importacion);
                        $esPdfFactura = strtolower(pathinfo($importacion->archivo_factura, PATHINFO_EXTENSION)) === 'pdf';
                    @endphp

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a href="{{ $urlFactura }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="btn btn-outline-danger">
                            <i class="bi bi-box-arrow-up-right"></i> Abrir en otra pestaña
                        </a>

                        <a href="{{ route('importaciones.factura.descargar', $importacion) }}"
                           class="btn btn-outline-secondary">
                            <i class="bi bi-download"></i> Descargar factura
                        </a>
                    </div>

                    @if($esPdfFactura)
                        <iframe src="{{ $urlFactura }}"
                                title="Factura de la importación #{{ $importacion->id }}"
                                class="w-100 border rounded"
                                style="height: 70vh; min-height: 420px;"></iframe>
                    @else
                        <a href="{{ $urlFactura }}" target="_blank" rel="noopener noreferrer">
                            <img src="{{ $urlFactura }}"
                                 alt="Factura de la importación #{{ $importacion->id }}"
                                 class="img-fluid border rounded"
                                 style="max-height: 70vh;">
                        </a>
                    @endif
                @else
                    <p class="text-muted mb-0">
                        No se adjuntó ningún documento para esta importación.
                    </p>
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
@include('importaciones._conteo')
@endsection

@section('scripts')
<script type="module" src="{{ asset('js/conteo-fisico.js') }}?v={{ filemtime(public_path('js/conteo-fisico.js')) }}"></script>
@endsection
