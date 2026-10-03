@extends('layouts.app')

@section('title', 'Crear Proveedor')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2 class="page-title">Nuevo Proveedor</h2>
    </div>
</div>

<div class="card card-custom" style="max-width: 800px;">
    <div class="card-header">Datos del Proveedor</div>
    <div class="card-body">
        <form action="{{ route('proveedores.store') }}" method="POST" novalidate>
            @csrf

            @include('proveedores._campos')

            <div class="mt-4">
                <button type="submit" class="btn btn-rojo">Registrar Proveedor</button>
                <a href="{{ route('proveedores.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
