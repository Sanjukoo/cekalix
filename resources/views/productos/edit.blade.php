@extends('layouts.app')

@section('title', 'Editar Producto')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2 class="page-title">Editar Producto</h2>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header">Actualizar Datos</div>
    <div class="card-body">
        <form action="{{ route('productos.update', $producto) }}" method="POST" novalidate
              data-confirm="Se guardarán los cambios del producto {{ $producto->codigo }}."
              data-confirm-titulo="Confirmar edición de producto"
              data-confirm-boton="Guardar cambios">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="codigo" class="form-label">Código <span class="text-danger">*</span></label>
                    <input type="text" id="codigo" name="codigo" class="form-control @error('codigo') is-invalid @enderror"
                           value="{{ old('codigo', $producto->codigo) }}" required>
                    @error('codigo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label for="nombre" class="form-label">Nombre del Producto <span class="text-danger">*</span></label>
                    <input type="text" id="nombre" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre', $producto->nombre) }}" required>
                    @error('nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label for="proveedor_id" class="form-label">Proveedor <span class="text-danger">*</span></label>
                    <select id="proveedor_id" name="proveedor_id" class="form-select @error('proveedor_id') is-invalid @enderror" required>
                        <option value="">-- Selecciona un proveedor --</option>
                        @foreach($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}" @selected(old('proveedor_id', $producto->proveedor_id) == $proveedor->id)>
                                {{ $proveedor->razon_social }}{{ $proveedor->activo ? '' : ' (inactivo)' }}
                            </option>
                        @endforeach
                    </select>
                    @error('proveedor_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="categoria_id" class="form-label">Categoría <span class="text-danger">*</span></label>
                    <select id="categoria_id" name="categoria_id" class="form-select @error('categoria_id') is-invalid @enderror"
                            required onchange="actualizarAtributos()">
                        <option value="">-- Selecciona una categoría --</option>
                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria->id }}" data-slug="{{ $categoria->slug }}" @selected(old('categoria_id', $producto->categoria_id) == $categoria->id)>
                                {{ $categoria->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('categoria_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label for="stock" class="form-label">Stock actual</label>
                    <input type="number" id="stock" class="form-control" value="{{ $producto->stock }}" disabled>
                    <small class="text-muted">
                        Solo cambia con ventas, cierres de importación o ajustes (ver kárdex).
                    </small>
                </div>
                <div class="col-md-4">
                    <label for="unidades_por_caja" class="form-label">Unidades por Caja <span class="text-danger">*</span></label>
                    <input type="number" id="unidades_por_caja" name="unidades_por_caja" class="form-control @error('unidades_por_caja') is-invalid @enderror"
                           value="{{ old('unidades_por_caja', $producto->unidades_por_caja) }}" min="1" required>
                    @error('unidades_por_caja')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="precio_venta" class="form-label">Precio de venta (S/ por unidad) <span class="text-danger">*</span></label>
                    <input type="number" id="precio_venta" name="precio_venta" class="form-control @error('precio_venta') is-invalid @enderror"
                           value="{{ old('precio_venta', $producto->precio_venta) }}" min="0" step="0.01" required>
                    @error('precio_venta')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea id="descripcion" name="descripcion" class="form-control" rows="3">{{ old('descripcion', $producto->descripcion) }}</textarea>
            </div>

            <hr>

            <!-- Atributos dinámicos por categoría -->
            <div id="atributos-container"></div>

            <div class="mt-4">
                <button type="submit" class="btn btn-rojo">Actualizar Producto</button>
                <a href="{{ route('productos.show', $producto) }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script src="{{ asset('js/atributos-producto.js') }}?v={{ filemtime(public_path('js/atributos-producto.js')) }}"></script>
@php
    // Valores guardados del producto; si hubo un error de validación, manda lo que se envió.
    $valoresAtributos = array_merge(
        $atributos,
        collect(old())
            ->filter(fn ($valor, $clave) => str_starts_with($clave, 'atributo_'))
            ->mapWithKeys(fn ($valor, $clave) => [substr($clave, strlen('atributo_')) => $valor])
            ->all()
    );
@endphp
<script>
    // Atributos de cada categoría con su lista de opciones (si la tiene).
    const atributosPorCategoria = {{ \Illuminate\Support\Js::from($atributosPorCategoria) }};

    const atributosActuales = {{ \Illuminate\Support\Js::from((object) $valoresAtributos) }};

    function actualizarAtributos() {
        const selectElement = document.getElementById('categoria_id');
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const categoriaSlug = selectedOption.dataset.slug;
        const container = document.getElementById('atributos-container');

        if (!categoriaSlug) {
            container.replaceChildren();
            return;
        }

        CekalixAtributos.dibujar(
            container,
            selectedOption.text.trim(),
            atributosPorCategoria[categoriaSlug],
            atributosActuales
        );
    }

    document.addEventListener('DOMContentLoaded', actualizarAtributos);
</script>
@endsection
