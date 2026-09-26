@extends('layouts.app')

@section('title', 'Crear Proveedor')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2 style="color: #000000;">Nuevo Proveedor</h2>
    </div>
</div>

<div class="card card-custom" style="max-width: 500px;">
    <div class="card-header">Datos del Proveedor</div>
    <div class="card-body">
        <form action="{{ route('proveedores.store') }}" method="POST" novalidate>
            @csrf

            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre del Proveedor <span class="text-danger">*</span></label>
                <input type="text" id="nombre" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                       value="{{ old('nombre') }}" required>
                @error('nombre')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" id="activo" name="activo" class="form-check-input" value="1" @checked(old('activo', true))>
                    <label class="form-check-label" for="activo">
                        Proveedor Activo
                    </label>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-rojo">Registrar Proveedor</button>
                <a href="{{ route('proveedores.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
