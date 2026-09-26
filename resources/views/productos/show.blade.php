@extends('layouts.app')

@section('title', 'Ver Producto')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h2 style="color: #000000;">Detalles del Producto</h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('productos.edit', $producto) }}" class="btn btn-warning">Editar</a>
        <a href="{{ route('productos.index') }}" class="btn btn-secondary">Volver</a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card card-custom mb-3">
            <div class="card-header">Información General</div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><strong>Código</strong></label>
                        <p class="form-control-plaintext">{{ $producto->codigo }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><strong>Nombre</strong></label>
                        <p class="form-control-plaintext">{{ $producto->nombre }}</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><strong>Categoría</strong></label>
                        <p class="form-control-plaintext"><span class="badge bg-info">{{ $producto->categoria->nombre }}</span></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><strong>Proveedor</strong></label>
                        <p class="form-control-plaintext">{{ $producto->proveedor }}</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><strong>Stock</strong></label>
                        <p class="form-control-plaintext">
                            @if($producto->stock < 10)
                                <span class="badge bg-danger">{{ $producto->stock }}</span>
                            @else
                                <span class="badge bg-success">{{ $producto->stock }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><strong>Unidades por Caja</strong></label>
                        <p class="form-control-plaintext">{{ $producto->unidades_por_caja }}</p>
                    </div>
                </div>

                @if($producto->descripcion)
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label"><strong>Descripción</strong></label>
                            <p class="form-control-plaintext">{{ $producto->descripcion }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if($producto->atributos->count() > 0)
            <div class="card card-custom">
                <div class="card-header">Atributos Específicos</div>
                <div class="card-body">
                    <div class="row">
                        @foreach($producto->atributos as $atributo)
                            <div class="col-md-6 mb-2">
                                <label class="form-label"><strong>{{ ucfirst($atributo->clave) }}</strong></label>
                                <p class="form-control-plaintext">{{ $atributo->valor }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="col-md-4">
        <div class="card card-custom bg-light">
            <div class="card-header">Datos de Auditoría</div>
            <div class="card-body small">
                <p><strong>Creado:</strong><br>{{ $producto->created_at->format('d/m/Y H:i') }}</p>
                <p><strong>Actualizado:</strong><br>{{ $producto->updated_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <div class="card card-custom mt-3">
            <div class="card-header">Acciones</div>
            <div class="card-body">
                <form action="{{ route('productos.destroy', $producto) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('¿Está seguro de que desea eliminar este producto?')">
                        Eliminar Producto
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
