@extends('layouts.app')

@section('title', 'Ver Producto')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="page-title">Detalles del Producto</h2>
    </div>
    <div class="col-md-4 text-end">
        @if(auth()->user()->puedeGestionarProductos())
            <a href="{{ route('productos.edit', $producto) }}" class="btn btn-negro">Editar</a>
        @endif
        <a href="{{ route('productos.index') }}" class="btn btn-outline-secondary">Volver</a>
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
                        <p class="form-control-plaintext"><span class="badge badge-categoria">{{ $producto->categoria->nombre }}</span></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><strong>Proveedor</strong></label>
                        <p class="form-control-plaintext">{{ $producto->proveedor->razon_social }}</p>
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
                    <div class="col-md-6">
                        <label class="form-label"><strong>Precio de venta (por unidad)</strong></label>
                        <p class="form-control-plaintext">S/ {{ number_format($producto->precio_venta, 2) }}</p>
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
        @if(in_array(auth()->user()->role, ['admin', 'inventario'], true))
            <div class="card card-custom mb-3">
                <div class="card-header">Stock y kárdex</div>
                <div class="card-body">
                    <p class="mb-2">
                        Stock actual: <strong>{{ $producto->stock }}</strong> unidades
                    </p>

                    <a href="#kardex" class="btn btn-outline-dark btn-sm w-100 mb-3">
                        Ver historial de movimientos
                    </a>

                    <form action="{{ route('productos.ajustar', $producto) }}" method="POST">
                        @csrf

                        <label for="cantidad" class="form-label">Ajuste (unidades)</label>
                        <input type="number" id="cantidad" name="cantidad" step="1"
                               class="form-control mb-2 @error('cantidad') is-invalid @enderror"
                               placeholder="Ej: 10 para sumar, -5 para restar"
                               value="{{ old('cantidad') }}" required>
                        @error('cantidad')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror

                        <label for="motivo" class="form-label">Motivo</label>
                        <input type="text" id="motivo" name="motivo" maxlength="255"
                               class="form-control mb-2 @error('motivo') is-invalid @enderror"
                               value="{{ old('motivo') }}" required>
                        @error('motivo')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror

                        @error('stock')
                            <div class="alert alert-danger py-2 small">{{ $message }}</div>
                        @enderror

                        <button type="submit" class="btn btn-negro w-100">
                            Registrar ajuste
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <div class="card card-custom bg-light">
            <div class="card-header">Datos de Auditoría</div>
            <div class="card-body small">
                <p><strong>Creado:</strong><br>{{ $producto->created_at->format('d/m/Y H:i') }}</p>
                <p><strong>Actualizado:</strong><br>{{ $producto->updated_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        @if(auth()->user()->puedeGestionarProductos())
            <div class="card card-custom mt-3">
                <div class="card-header">Acciones</div>
                <div class="card-body">
                    <form action="{{ route('productos.destroy', $producto) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-rojo w-100" onclick="return confirm('¿Está seguro de que desea eliminar este producto?')">
                            Eliminar Producto
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>

@if(in_array(auth()->user()->role, ['admin', 'inventario'], true))
    <div class="card card-custom mt-4" id="kardex">
        <div class="card-header">
            Historial de movimientos (kárdex)
            <small class="text-muted fw-normal ms-2">últimos {{ $movimientos->count() }} movimientos</small>
        </div>
        <div class="card-body">
            @if($movimientos->isEmpty())
                <p class="text-center text-muted py-3 mb-0">Este producto todavía no tiene movimientos.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Movimiento</th>
                                <th class="text-end">Cantidad</th>
                                <th class="text-end">Saldo</th>
                                <th>Detalle</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($movimientos as $movimiento)
                                <tr>
                                    <td class="text-nowrap">{{ $movimiento->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ \App\Models\MovimientoInventario::TIPOS[$movimiento->tipo] ?? $movimiento->tipo }}</td>
                                    <td class="text-end">
                                        <strong class="{{ $movimiento->cantidad > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $movimiento->cantidad > 0 ? '+' : '' }}{{ $movimiento->cantidad }}
                                        </strong>
                                    </td>
                                    <td class="text-end">{{ $movimiento->saldo }}</td>
                                    <td><small>{{ $movimiento->motivo ?? '—' }}</small></td>
                                    <td>{{ $movimiento->usuario?->name ?? 'Sistema' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endif
@endsection
