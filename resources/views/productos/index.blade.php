@extends('layouts.app')

@section('title', 'Productos')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h2 style="color: #000000;">Productos Registrados</h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('productos.create') }}" class="btn btn-rojo">
            Nuevo Producto
        </a>
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
                                <td><span class="badge bg-info">{{ $producto->categoria->nombre }}</span></td>
                                <td>{{ $producto->proveedor }}</td>
                                <td>
                                    @if($producto->stock < 10)
                                        <span class="badge bg-danger">{{ $producto->stock }}</span>
                                    @else
                                        <span class="badge bg-success">{{ $producto->stock }}</span>
                                    @endif
                                </td>
                                <td>{{ $producto->unidades_por_caja }}</td>
                                <td>
                                    <a href="{{ route('productos.show', $producto) }}" class="btn btn-sm btn-info" title="Ver">Ver</a>
                                    <a href="{{ route('productos.edit', $producto) }}" class="btn btn-sm btn-warning" title="Editar">Editar</a>
                                    <form action="{{ route('productos.destroy', $producto) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro?')" title="Eliminar">Eliminar</button>
                                    </form>
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
