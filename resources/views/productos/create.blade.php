@extends('layouts.app')

@section('title', 'Crear Producto')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2 class="page-title">Nuevo Producto</h2>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header">Datos del Producto</div>

    <div class="card-body">
        <form action="{{ route('productos.store') }}" method="POST" novalidate>
            @csrf

            {{-- Selección de importación y SKU --}}
            <div class="mb-3">
                <label for="importacion_detalle_id" class="form-label">
                    Importación y SKU <span class="text-danger">*</span>
                </label>

                <select id="importacion_detalle_id"
                        name="importacion_detalle_id"
                        class="form-select @error('importacion_detalle_id') is-invalid @enderror"
                        required>

                    <option value="">-- Selecciona una importación y SKU --</option>

                    @foreach($detallesPendientes as $detalle)
                        <option
                            value="{{ $detalle->id }}"
                            data-codigo="{{ $detalle->producto_codigo }}"
                            data-proveedor-id="{{ $detalle->importacion->proveedor_id }}"
                            data-proveedor="{{ $detalle->importacion->proveedor->razon_social }}"
                            data-nombre="{{ $detalle->producto->nombre ?? '' }}"
                            data-categoria-id="{{ $detalle->producto->categoria_id ?? '' }}"
                            data-unidades="{{ $detalle->producto->unidades_por_caja ?? 1 }}"
                            data-descripcion="{{ $detalle->producto->descripcion ?? '' }}"
                            @selected(old('importacion_detalle_id') == $detalle->id)
                        >
                            Importación #{{ $detalle->importacion_id }}
                            — SKU: {{ $detalle->producto_codigo }}
                            — Factura: {{ $detalle->importacion->numero_factura }}
                            — Proveedor: {{ $detalle->importacion->proveedor->razon_social }}

                            @if($detalle->producto)
                                — Producto existente
                            @else
                                — Producto nuevo
                            @endif
                        </option>
                    @endforeach
                </select>

                @error('importacion_detalle_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                @if($detallesPendientes->isEmpty())
                    <small class="text-muted">
                        No hay importaciones pendientes de revisión en inventario.
                    </small>
                @else
                    <small class="text-muted">
                        Selecciona la importación que deseas registrar o revisar.
                        Si el SKU ya existe, se reutilizará su ficha de producto.
                    </small>
                @endif
            </div>

            {{-- Código y proveedor --}}
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="codigo" class="form-label">Código / SKU</label>

                    <input
                        type="text"
                        id="codigo"
                        class="form-control"
                        value=""
                        readonly
                    >
                </div>

                <div class="col-md-6">
                    <label for="proveedor_nombre" class="form-label">Proveedor de la importación</label>

                    <input
                        type="text"
                        id="proveedor_nombre"
                        class="form-control"
                        value=""
                        readonly
                    >

                    <input
                        type="hidden"
                        id="proveedor_id"
                        name="proveedor_id"
                        value="{{ old('proveedor_id') }}"
                    >
                </div>
            </div>

            {{-- Nombre --}}
            <div class="mb-3">
                <label for="nombre" class="form-label">
                    Nombre del producto <span class="text-danger">*</span>
                </label>

                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    class="form-control @error('nombre') is-invalid @enderror"
                    value="{{ old('nombre') }}"
                    maxlength="100"
                    required
                >

                @error('nombre')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Categoría y unidades --}}
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="categoria_id" class="form-label">
                        Categoría <span class="text-danger">*</span>
                    </label>

                    <select
                        id="categoria_id"
                        name="categoria_id"
                        class="form-select @error('categoria_id') is-invalid @enderror"
                        required
                        onchange="actualizarAtributos()"
                    >
                        <option value="">-- Selecciona una categoría --</option>

                        @foreach($categorias as $categoria)
                            <option
                                value="{{ $categoria->id }}"
                                data-slug="{{ $categoria->slug }}"
                                @selected(old('categoria_id') == $categoria->id)
                            >
                                {{ $categoria->nombre }}
                            </option>
                        @endforeach

                        <option value="nueva">+ Nueva categoría</option>
                    </select>

                    @error('categoria_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="unidades_por_caja" class="form-label">
                        Unidades por caja <span class="text-danger">*</span>
                    </label>

                    <input
                        type="number"
                        id="unidades_por_caja"
                        name="unidades_por_caja"
                        class="form-control @error('unidades_por_caja') is-invalid @enderror"
                        value="{{ old('unidades_por_caja', 1) }}"
                        min="1"
                        required
                    >

                    <small class="text-muted">
                        El stock se suma al cerrar la recepción de la importación.
                    </small>

                    @error('unidades_por_caja')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Descripción --}}
            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>

                <textarea
                    id="descripcion"
                    name="descripcion"
                    class="form-control @error('descripcion') is-invalid @enderror"
                    rows="3"
                >{{ old('descripcion') }}</textarea>

                @error('descripcion')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <hr>

            {{-- Atributos dinámicos de la categoría --}}
            <div id="atributos-container"></div>

            <div class="mt-4">
                <button
                    type="submit"
                    class="btn btn-rojo"
                    @disabled($detallesPendientes->isEmpty())
                >
                    Registrar / actualizar producto
                </button>

                <a href="{{ route('productos.index') }}"
                   class="btn btn-outline-secondary">
                    Cancelar
                </a>
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

// Atributos actuales de los productos vinculados a cada detalle.
const atributosExistentes = @json(
    $detallesPendientes->mapWithKeys(function ($detalle) {
        return [
            $detalle->id => $detalle->producto
                ? $detalle->producto->atributos->pluck('valor', 'clave')
                : [],
        ];
    })
);

// Datos anteriores enviados por el formulario, si hubo errores de validación.
const valoresAnteriores = @json(old());

const selectorImportacion =
    document.getElementById('importacion_detalle_id');

const campoCodigo = document.getElementById('codigo');
const campoProveedor = document.getElementById('proveedor_nombre');
const campoProveedorId = document.getElementById('proveedor_id');
const campoNombre = document.getElementById('nombre');
const campoCategoria = document.getElementById('categoria_id');
const campoUnidades = document.getElementById('unidades_por_caja');
const campoDescripcion = document.getElementById('descripcion');

function escaparHtml(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, caracter => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[caracter]);
}

function actualizarDatosImportacion() {
    const opcion =
        selectorImportacion.options[selectorImportacion.selectedIndex];

    if (!opcion || !opcion.value) {
        campoCodigo.value = '';
        campoProveedor.value = '';
        campoProveedorId.value = '';
        campoNombre.value = '';
        campoCategoria.value = '';
        campoUnidades.value = 1;
        campoDescripcion.value = '';

        actualizarAtributos();
        return;
    }

    campoCodigo.value = opcion.dataset.codigo || '';
    campoProveedor.value = opcion.dataset.proveedor || '';
    campoProveedorId.value = opcion.dataset.proveedorId || '';

    // Precargar la ficha cuando el SKU ya existe.
    campoNombre.value = opcion.dataset.nombre || '';
    campoCategoria.value = opcion.dataset.categoriaId || '';
    campoUnidades.value = opcion.dataset.unidades || 1;
    campoDescripcion.value = opcion.dataset.descripcion || '';

    actualizarAtributos();
}

function actualizarAtributos() {
    const opcionCategoria =
        campoCategoria.options[campoCategoria.selectedIndex];

    const container = document.getElementById('atributos-container');

    if (!opcionCategoria || opcionCategoria.value === 'nueva') {
        if (opcionCategoria && opcionCategoria.value === 'nueva') {
            window.location.href = "{{ route('categorias.create') }}";
        }

        container.innerHTML = '';
        return;
    }

    container.innerHTML = '';

    if (!opcionCategoria.value) {
        return;
    }

    const slug = opcionCategoria.dataset.slug;
    const atributos = slug ? atributosPorCategoria[slug] : null;

    if (!atributos || atributos.length === 0) {
        container.innerHTML =
            '<p class="text-muted">Esta categoría no tiene atributos definidos.</p>';
        return;
    }

    const opcionImportacion =
        selectorImportacion.options[selectorImportacion.selectedIndex];

    const valoresProducto = opcionImportacion && opcionImportacion.value
        ? (atributosExistentes[opcionImportacion.value] || {})
        : {};

    let html = `
        <h6 class="text-danger">
            Atributos de ${escaparHtml(opcionCategoria.text)}
        </h6>
        <div class="row">
    `;

    atributos.forEach(atributo => {
        const nombreCampo = 'atributo_' + atributo.campo;

        // Priorizar los valores enviados si el formulario tuvo errores.
        const valor = Object.prototype.hasOwnProperty.call(
            valoresAnteriores,
            nombreCampo
        )
            ? valoresAnteriores[nombreCampo]
            : (valoresProducto[atributo.campo] ?? '');

        html += `
            <div class="col-md-6 mb-3">
                <label
                    for="${escaparHtml(nombreCampo)}"
                    class="form-label"
                >
                    ${escaparHtml(atributo.nombre)}
                </label>

                <input
                    type="text"
                    id="${escaparHtml(nombreCampo)}"
                    name="${escaparHtml(nombreCampo)}"
                    class="form-control"
                    value="${escaparHtml(valor)}"
                >
            </div>
        `;
    });

    container.innerHTML = html + '</div>';
}

selectorImportacion.addEventListener(
    'change',
    actualizarDatosImportacion
);

document.addEventListener('DOMContentLoaded', function () {
    actualizarDatosImportacion();
});
</script>
@endsection
