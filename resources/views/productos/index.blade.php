@extends('layouts.app')

@section('title', 'Productos')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="page-title">Productos Registrados</h2>
    </div>
    @if(auth()->user()->puedeGestionarProductos())
        <div class="col-md-4 text-end">
            <a href="{{ route('productos.create') }}" class="btn btn-rojo">
                Nuevo Producto
            </a>
        </div>
    @endif
</div>


<div class="card card-custom mb-4">
    <div class="card-body">
        <form action="{{ route('productos.index') }}" method="GET">
            <div class="row g-2">
                <div class="col-md-9">
                    <input
                        type="text"
                        name="buscar"
                        class="form-control"
                        placeholder="Buscar por SKU, nombre o características..."
                        value="{{ $buscar }}"
                    >
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-rojo">
                        Buscar
                    </button>

                    <a href="{{ route('productos.index') }}"
                       class="btn btn-outline-secondary">
                        Limpiar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body">
        @if($productos->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Proveedor</th>
                            <th>Stock</th>
                            <th>Unidad/Caja</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productos as $producto)
                            <tr>
                                <td><strong>{{ $producto->codigo }}</strong></td>
                                <td>{{ $producto->nombre }}</td>
                                <td><span class="badge badge-categoria">{{ $producto->categoria->nombre }}</span></td>
                                <td>{{ $producto->proveedor->razon_social }}</td>
                                <td>
                                    @if($producto->stock < 10)
                                        <span class="badge bg-danger">{{ $producto->stock }}</span>
                                    @else
                                        <span class="badge bg-success">{{ $producto->stock }}</span>
                                    @endif
                                </td>
                                <td>{{ $producto->unidades_por_caja }}</td>
                                <td>
                                    <a href="{{ route('productos.show', $producto) }}" class="btn btn-sm btn-outline-dark" title="Ver">Ver</a>
                                    @if(auth()->user()->puedeGestionarProductos())
                                        <a href="{{ route('productos.edit', $producto) }}" class="btn btn-sm btn-negro" title="Editar">Editar</a>
                                        <form action="{{ route('productos.destroy', $producto) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-rojo" onclick="return confirm('¿Está seguro?')" title="Eliminar">Eliminar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $productos->links() }}
            </div>
        @else
            <p class="text-center text-muted py-4">No hay productos registrados.</p>
        @endif
    </div>
</div>
@endsection
