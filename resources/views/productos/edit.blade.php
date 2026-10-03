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
        <form action="{{ route('productos.update', $producto) }}" method="POST" novalidate>
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
                    <label for="stock" class="form-label">Stock <span class="text-danger">*</span></label>
                    <input type="number" id="stock" name="stock" class="form-control @error('stock') is-invalid @enderror"
                           value="{{ old('stock', $producto->stock) }}" min="0" required>
                    @error('stock')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
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

<script>
    const atributosPorCategoria = @json(
        $categorias->mapWithKeys(function ($categoria) {
            return [
                $categoria->slug => $categoria->atributos->map(function ($atributo) {
                    return [
                        'nombre' => $atributo->nombre,
                        'campo' => \Illuminate\Support\Str::slug($atributo->nombre)
                    ];
                })
            ];
        })
    );

    const atributosActuales = @json((object) $atributos);

    function actualizarAtributos() {
        const selectElement = document.getElementById('categoria_id');
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const categoriaSlug = selectedOption.dataset.slug;

        const container = document.getElementById('atributos-container');
        container.innerHTML = '';

        if (categoriaSlug && atributosPorCategoria[categoriaSlug]) {
            const titulo = document.createElement('h6');
            titulo.className = 'text-danger';
            titulo.textContent = 'Atributos de ' + selectedOption.text.trim();

            const fila = document.createElement('div');
            fila.className = 'row';

            // Se arma con el DOM para que los valores guardados no se interpreten como HTML
            atributosPorCategoria[categoriaSlug].forEach(atributo => {
                const columna = document.createElement('div');
                columna.className = 'col-md-6 mb-3';

                const label = document.createElement('label');
                label.className = 'form-label';
                label.htmlFor = 'atributo_' + atributo.campo;
                label.textContent = atributo.nombre;

                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
                input.id = input.name = 'atributo_' + atributo.campo;
                input.value = atributosActuales[atributo.campo] || '';

                columna.append(label, input);
                fila.append(columna);
            });

            container.append(titulo, fila);
        }
    }

    document.addEventListener('DOMContentLoaded', actualizarAtributos);
</script>
@endsection
