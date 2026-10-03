@extends('layouts.app')

@section('title', 'Nueva Importación')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2 class="page-title">Nueva Importación</h2>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header">Datos de la Importación</div>
    <div class="card-body">
        <form action="{{ route('importaciones.store') }}" method="POST" novalidate>
            @csrf

            <h6 class="text-danger mb-3">Datos Generales</h6>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="proveedor_id" class="form-label">Proveedor <span class="text-danger">*</span></label>
                    <select id="proveedor_id" name="proveedor_id" class="form-select @error('proveedor_id') is-invalid @enderror" required>
                        <option value="">-- Seleccionar proveedor registrado --</option>
                        @foreach($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}" @selected(old('proveedor_id') == $proveedor->id)>
                                {{ $proveedor->razon_social }}
                            </option>
                        @endforeach
                    </select>
                    @error('proveedor_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="fecha_llegada" class="form-label">Fecha de Llegada <span class="text-danger">*</span></label>
                    <input type="date" id="fecha_llegada" name="fecha_llegada" class="form-control @error('fecha_llegada') is-invalid @enderror"
                           value="{{ old('fecha_llegada') }}" required>
                    @error('fecha_llegada')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="numero_factura" class="form-label">Nº de Factura <span class="text-danger">*</span></label>
                    <input type="text" id="numero_factura" name="numero_factura" class="form-control @error('numero_factura') is-invalid @enderror"
                           placeholder="Ej: FAC-00125" value="{{ old('numero_factura') }}" required>
                    @error('numero_factura')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="numero_contenedor" class="form-label">Nº de Contenedor <span class="text-danger">*</span></label>
                    <input type="text" id="numero_contenedor" name="numero_contenedor" class="form-control @error('numero_contenedor') is-invalid @enderror"
                           placeholder="Ej: CONT-001" value="{{ old('numero_contenedor') }}" required>
                    @error('numero_contenedor')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <hr>

            <h6 class="text-danger mb-3">Detalle de Mercadería</h6>

            <div class="row mb-3">
                <div class="col-md-7">
                    <label for="producto_id" class="form-label">SKU / Producto <span class="text-danger">*</span></label>
                    <select id="producto_id" name="producto_id" class="form-select @error('producto_id') is-invalid @enderror" required>
                        <option value="">-- Seleccionar SKU existente --</option>
                        @foreach($productos as $producto)
                            <option value="{{ $producto->id }}" @selected(old('producto_id') == $producto->id)>
                                {{ $producto->codigo }} - {{ $producto->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('producto_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-5">
                    <label for="cajas_facturadas" class="form-label">Cajas Facturadas <span class="text-danger">*</span></label>
                    <input type="number" id="cajas_facturadas" name="cajas_facturadas" class="form-control @error('cajas_facturadas') is-invalid @enderror"
                           placeholder="Ej: 50" min="1" value="{{ old('cajas_facturadas') }}" required>
                    @error('cajas_facturadas')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-rojo">Registrar Importación</button>
                <a href="{{ route('importaciones.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
