@extends('layouts.app')

@section('title', 'Editar Proveedor')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2 class="page-title">Editar Proveedor</h2>
    </div>
</div>

<div class="card card-custom" style="max-width: 800px;">
    <div class="card-header">Actualizar Datos</div>
    <div class="card-body">
        <form action="{{ route('proveedores.update', $proveedor) }}" method="POST" novalidate>
            @csrf
            @method('PUT')

            @include('proveedores._campos', ['proveedor' => $proveedor])

            <div class="mt-4">
                <button type="submit" class="btn btn-rojo">Actualizar Proveedor</button>
                <a href="{{ route('proveedores.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
