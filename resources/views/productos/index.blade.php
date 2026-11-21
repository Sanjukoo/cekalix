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
            {{-- Conserva la categoría elegida al buscar --}}
            @if($categoriaActual)
                <input type="hidden" name="categoria" value="{{ $categoriaActual->id }}">
            @endif

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

        @if($categorias->isNotEmpty())
            @php
                // Los filtros conservan la búsqueda escrita.
                $filtroUrl = fn ($categoriaId = null) => route('productos.index', array_filter([
                    'buscar' => $buscar !== '' ? $buscar : null,
                    'categoria' => $categoriaId,
                ]));
            @endphp

            <nav class="filtro-categorias mt-3" aria-label="Filtrar por categoría">
                <span class="filtro-categorias-titulo">Categoría:</span>

                <a href="{{ $filtroUrl() }}"
                   class="filtro-chip {{ $categoriaActual ? '' : 'activo' }}"
                   @if(!$categoriaActual) aria-current="true" @endif>
                    Todas
                    <span class="filtro-chip-total">{{ $conteoPorCategoria->sum() }}</span>
                </a>

                @foreach($categorias as $categoria)
                    @php
                        $totalCategoria = (int) ($conteoPorCategoria[$categoria->id] ?? 0);
                        $activa = $categoriaActual?->id === $categoria->id;
                    @endphp

                    <a href="{{ $filtroUrl($categoria->id) }}"
                       class="filtro-chip {{ $activa ? 'activo' : '' }} {{ $totalCategoria === 0 && !$activa ? 'vacio' : '' }}"
                       @if($activa) aria-current="true" @endif>
                        {{ $categoria->nombre }}
                        <span class="filtro-chip-total">{{ $totalCategoria }}</span>
                    </a>
                @endforeach
            </nav>
        @endif
    </div>
</div>

@if($categoriaActual || $buscar !== '')
    <p class="text-muted mb-3">
        {{ $productos->total() }} {{ $productos->total() === 1 ? 'producto' : 'productos' }}
        @if($categoriaActual)
            en <strong>{{ $categoriaActual->nombre }}</strong>
        @endif
        @if($buscar !== '')
            que coinciden con «<strong>{{ $buscar }}</strong>»
        @endif
    </p>
@endif

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
                            <th>Stock (cajas)</th>
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
                                    @php
                                        $unidadesPorCaja = max(1, (int) $producto->unidades_por_caja);
                                        $cajasDisponibles = intdiv((int) $producto->stock, $unidadesPorCaja);
                                        $unidadesSueltas = (int) $producto->stock % $unidadesPorCaja;
                                    @endphp

                                    @if($cajasDisponibles < 10)
                                        <span class="badge bg-danger">{{ $cajasDisponibles }}</span>
                                    @else
                                        <span class="badge bg-success">{{ $cajasDisponibles }}</span>
                                    @endif

                                    @if($unidadesSueltas > 0)
                                        <small class="text-muted d-block">
                                            + {{ $unidadesSueltas }} unidades sueltas
                                        </small>
                                    @endif
                                </td>

                                <td>{{ $producto->unidades_por_caja }}</td>

                                <td>
                                    <div class="acciones-tabla">
                                        <a href="{{ route('productos.show', $producto) }}" class="btn btn-sm btn-outline-dark" title="Ver">Ver</a>

                                        @if(auth()->user()->puedeGestionarProductos())
                                            <a href="{{ route('productos.edit', $producto) }}" class="btn btn-sm btn-negro" title="Editar">Editar</a>

                                            <form action="{{ route('productos.destroy', $producto) }}"
                                                  method="POST"
                                                  data-confirm="¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer."
                                                  data-confirm-titulo="Confirmar eliminación"
                                                  data-confirm-boton="Eliminar producto"
                                                  data-confirm-datos="{{ json_encode([
                                                      'Código' => $producto->codigo,
                                                      'Producto' => $producto->nombre,
                                                      'Categoría' => $producto->categoria->nombre,
                                                      'Stock actual' => $producto->stock.' unidades',
                                                  ]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-rojo" title="Eliminar">Eliminar</button>
                                            </form>
                                        @endif
                                    </div>
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
            @if($categoriaActual || $buscar !== '')
                <div class="text-center text-muted py-4">
                    <p class="mb-2">No se encontraron productos con estos filtros.</p>
                    <a href="{{ route('productos.index') }}" class="btn btn-sm btn-outline-secondary">
                        Ver todo el catálogo
                    </a>
                </div>
            @else
                <p class="text-center text-muted py-4">No hay productos registrados.</p>
            @endif
        @endif
    </div>
</div>
@endsection
