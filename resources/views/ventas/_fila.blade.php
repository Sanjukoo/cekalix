<div class="row mb-3 venta-fila align-items-end">
    <div class="col-md-4">
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
                    (S/ {{ number_format($producto->precio_venta, 2) }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-2">
        <label class="form-label">
            Cajas <span class="text-danger">*</span>
        </label>

        <input type="number"
               name="productos[{{ $indice }}][cajas]"
               class="form-control cajas-input"
               min="1"
               step="1"
               placeholder="Ej: 2"
               value="{{ $linea['cajas'] ?? '' }}"
               required>
    </div>

    <div class="col-md-3">
        <label class="form-label">Información de stock</label>

        <small class="d-block text-muted disponible">
            Selecciona un producto
        </small>

        <small class="d-block fw-bold unidades-total">
            Unidades: 0
        </small>

        <small class="d-block stock-restante">
            Stock restante: 0
        </small>
    </div>

    <div class="col-md-2">
        <label class="form-label">Subtotal</label>

        <p class="form-control-plaintext fw-bold subtotal-fila">
            S/ 0.00
        </p>
    </div>

    <div class="col-md-1">
        <button type="button"
                class="btn btn-outline-danger w-100 eliminar-fila">
            Quitar
        </button>
    </div>
</div>
