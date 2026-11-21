@php
    // Una línea nueva empieza sin modalidad: el vendedor elige "por caja" o "por unidad".
    $modalidadLinea = in_array($linea['modalidad'] ?? '', [
        \App\Models\VentaDetalle::POR_CAJA,
        \App\Models\VentaDetalle::POR_UNIDAD,
    ], true) ? $linea['modalidad'] : '';

    $etiquetaCantidad = match ($modalidadLinea) {
        \App\Models\VentaDetalle::POR_CAJA => 'Cajas',
        \App\Models\VentaDetalle::POR_UNIDAD => 'Unidades',
        default => 'Cantidad',
    };
@endphp

{{-- Todas las columnas alinean su etiqueta arriba; los textos de ayuda van debajo de cada campo. --}}
<div class="venta-fila mb-3 pb-3 border-bottom" data-resumen-fila>
<div class="row g-3 align-items-start">
    <div class="col-lg-4 col-md-12">
        <label class="form-label">
            Producto <span class="text-danger">*</span>
        </label>

        <select name="productos[{{ $indice }}][producto_id]"
                class="form-select producto-select" required>
            <option value="">-- Seleccionar producto --</option>

            @foreach($productos as $producto)
                <option value="{{ $producto->id }}"
                        data-precio="{{ $producto->precio_venta }}"
                        data-stock="{{ $producto->stock_disponible ?? $producto->stock }}"
                        data-unidades="{{ $producto->unidades_por_caja }}"
                        @selected(($linea['producto_id'] ?? '') == $producto->id)>
                    {{ $producto->codigo }} - {{ $producto->nombre }}
                    (S/ {{ number_format($producto->precio_venta, 2) }} c/u)
                </option>
            @endforeach
        </select>

        <small class="d-block text-muted mt-1 disponible">Selecciona un producto</small>
    </div>

    <div class="col-lg-2 col-md-4">
        <label class="form-label">
            Vender por <span class="text-danger">*</span>
        </label>

        <select name="productos[{{ $indice }}][modalidad]"
                class="form-select modalidad-select" required>
            <option value="" @selected($modalidadLinea === '')>Seleccionar</option>
            <option value="{{ \App\Models\VentaDetalle::POR_CAJA }}"
                    @selected($modalidadLinea === \App\Models\VentaDetalle::POR_CAJA)>
                Por caja
            </option>
            <option value="{{ \App\Models\VentaDetalle::POR_UNIDAD }}"
                    @selected($modalidadLinea === \App\Models\VentaDetalle::POR_UNIDAD)>
                Por unidad
            </option>
        </select>
    </div>

    <div class="col-lg-2 col-md-4">
        <label class="form-label">
            <span class="cantidad-etiqueta">{{ $etiquetaCantidad }}</span>
            <span class="text-danger">*</span>
        </label>

        <input type="number"
               name="productos[{{ $indice }}][cantidad]"
               class="form-control cantidad-input"
               data-resumen="Cantidad"
               min="1"
               step="1"
               placeholder="Ej: 2"
               value="{{ $linea['cantidad'] ?? '' }}"
               required>

        <small class="d-block text-muted mt-1 ayuda-modalidad">&nbsp;</small>
    </div>

    <div class="col-lg-2 col-md-4">
        <label class="form-label">Unidades</label>

        <p class="form-control-plaintext fw-bold py-2 mb-0 unidades-total">0</p>

        <small class="d-block mt-1 stock-restante">&nbsp;</small>

        <span class="d-none unidades-resumen" data-resumen-valor="Unidades"></span>
    </div>

    <div class="col-lg-2 col-md-12">
        <label class="form-label">Subtotal</label>

        <div class="d-flex align-items-center gap-2">
            <p class="form-control-plaintext fw-bold py-2 mb-0 flex-grow-1 text-nowrap subtotal-fila"
               data-resumen-valor="Subtotal">
                S/ 0.00
            </p>

            <button type="button"
                    class="btn btn-outline-danger eliminar-fila"
                    title="Quitar producto">
                Quitar
            </button>
        </div>
    </div>
</div>
</div>
