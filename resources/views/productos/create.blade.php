@extends('layouts.app')

@section('title', 'Crear Producto')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2 style="color: #000000;">Nuevo Producto</h2>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header">Datos del Producto</div>
    <div class="card-body">
        <form action="{{ route('productos.store') }}" method="POST" novalidate>
            @csrf

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="codigo" class="form-label">Código <span class="text-danger">*</span></label>
                    <input type="text" id="codigo" name="codigo" class="form-control @error('codigo') is-invalid @enderror"
                           value="{{ old('codigo') }}" required>
                    @error('codigo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label for="nombre" class="form-label">Nombre del Producto <span class="text-danger">*</span></label>
                    <input type="text" id="nombre" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre') }}" required>
                    @error('nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label for="proveedor" class="form-label">Proveedor <span class="text-danger">*</span></label>
                    <input type="text" id="proveedor" name="proveedor" class="form-control @error('proveedor') is-invalid @enderror"
                           value="{{ old('proveedor') }}" required>
                    @error('proveedor')
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
                            <option value="{{ $categoria->id }}" @selected(old('categoria_id') == $categoria->id)>
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
                           value="{{ old('stock', 0) }}" min="0" required>
                    @error('stock')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label for="unidades_por_caja" class="form-label">Unidades por Caja <span class="text-danger">*</span></label>
                    <input type="number" id="unidades_por_caja" name="unidades_por_caja" class="form-control @error('unidades_por_caja') is-invalid @enderror"
                           value="{{ old('unidades_por_caja', 1) }}" min="1" required>
                    @error('unidades_por_caja')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea id="descripcion" name="descripcion" class="form-control" rows="3">{{ old('descripcion') }}</textarea>
            </div>

            <hr>

            <!-- Atributos dinámicos por categoría -->
            <div id="atributos-container"></div>

            <div class="mt-4">
                <button type="submit" class="btn btn-rojo">Registrar Producto</button>
                <a href="{{ route('productos.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
    const atributosPorCategoria = {
        'correderas': ['longitud', 'espesor', 'ancho', 'color'],
        'bisagras': ['tipo', 'acabado', 'peso'],
        'pistones': ['fuerza', 'longitud', 'acabado'],
        'cerraduras': ['material', 'tamaño'],
    };

    function actualizarAtributos() {
        const categoriaId = document.getElementById('categoria_id').value;
        const selectElement = document.getElementById('categoria_id');
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const categoriaSlug = selectedOption.text.toLowerCase();

        const container = document.getElementById('atributos-container');
        container.innerHTML = '';

        if (categoriaSlug && atributosPorCategoria[categoriaSlug]) {
            const atributos = atributosPorCategoria[categoriaSlug];
            const labels = {
                'longitud': 'Longitud',
                'espesor': 'Espesor',
                'ancho': 'Ancho',
                'color': 'Color',
                'tipo': 'Tipo',
                'acabado': 'Acabado',
                'peso': 'Peso',
                'fuerza': 'Fuerza',
                'material': 'Material',
                'tamaño': 'Tamaño'
            };

            let html = '<h6 class="text-danger">Atributos de ' + selectedOption.text + '</h6><div class="row">';
            atributos.forEach(atributo => {
                html += `
                    <div class="col-md-6 mb-3">
                        <label for="atributo_${atributo}" class="form-label">${labels[atributo] || atributo}</label>
                        <input type="text" id="atributo_${atributo}" name="atributo_${atributo}" class="form-control">
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }
    }

    document.addEventListener('DOMContentLoaded', actualizarAtributos);
</script>
@endsection
