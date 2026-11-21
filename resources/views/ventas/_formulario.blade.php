@if($productos->isEmpty())
    <div class="alert alert-warning">
        No hay productos disponibles para vender. Verifica que tengan stock, precio de venta y unidades por caja asignadas.
    </div>
@endif

@php
    // Al editar, cada línea conserva su modalidad: cajas vendidas o unidades sueltas.
    $lineasVenta = $venta
        ? $venta->detalles->map(fn ($d) => [
            'producto_id' => $d->producto_id,
            'modalidad' => $d->modalidad(),
            'cantidad' => $d->esPorCaja() ? $d->cajas : $d->cantidad,
        ])->values()->all()
        : [['producto_id' => '', 'modalidad' => '', 'cantidad' => '']];

    $lineasIniciales = old('productos', $lineasVenta);
@endphp

<div class="card card-custom">
    <div class="card-header">Datos de la Venta</div>

    <div class="card-body">

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ $action }}" method="POST" id="formulario-venta"
              @if($venta)
                  data-confirm="Se guardarán los cambios de la venta {{ $venta->numero }}. El stock de las líneas anteriores se devolverá y se descontará el de las nuevas."
                  data-confirm-titulo="Confirmar edición de venta"
                  data-confirm-boton="Guardar cambios"
              @else
                  data-confirm="Revisa los datos de la venta. Al confirmar se descontará el stock de los productos."
                  data-confirm-titulo="Confirmar registro de venta"
                  data-confirm-boton="Registrar venta"
              @endif
        >
            @csrf

            @if(isset($venta))
                @method('PUT')
            @endif

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="cliente_id" class="form-label">Cliente</label>

                    <select id="cliente_id" name="cliente_id"
                            class="form-select @error('cliente_id') is-invalid @enderror">

                        <option value="">Cliente general</option>

                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}"
                                @selected(old('cliente_id', $venta?->cliente_id) == $cliente->id)>
                                {{ $cliente->nombre }}

                                @if($cliente->numero_documento)
                                    ({{ $cliente->tipo_documento }} {{ $cliente->numero_documento }})
                                @endif
                            </option>
                        @endforeach
                    </select>

                    <div class="form-text">
                        ¿El cliente no está en la lista?
                        <a href="{{ route('clientes.create') }}">Registrar cliente</a>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="fecha_venta" class="form-label">
                        Fecha de Venta <span class="text-danger">*</span>
                    </label>

                    <input type="date" id="fecha_venta" name="fecha_venta"
                           class="form-control @error('fecha_venta') is-invalid @enderror"
                           value="{{ old('fecha_venta', $venta?->fecha_venta?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                           required>

                    @error('fecha_venta')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <hr>

            <h6 class="text-danger mb-3">Productos Vendidos</h6>

            <p class="text-muted">
                Elige si cada producto se vende <strong>por caja</strong> o <strong>por unidad</strong>
                e ingresa la cantidad. El sistema calculará automáticamente las unidades
                y el stock restante. El stock solo se actualizará cuando registres la venta.
            </p>

            <div id="productos-container" data-resumen-grupo="Productos">
                @foreach($lineasIniciales as $i => $linea)
                    @include('ventas._fila', [
                        'indice' => $i,
                        'productos' => $productos,
                        'linea' => $linea,
                    ])
                @endforeach
            </div>

            <template id="plantilla-fila">
                @include('ventas._fila', [
                    'indice' => '__INDICE__',
                    'productos' => $productos,
                    'linea' => [],
                ])
            </template>

            <button type="button" id="agregar-producto"
                    class="btn btn-outline-secondary mb-3"
                    {{ $productos->isEmpty() ? 'disabled' : '' }}>
                + Agregar otro producto
            </button>

            <hr>

            <div class="row justify-content-end">
                <div class="col-md-5">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <strong id="subtotal-venta" data-resumen-valor="Subtotal">S/ 0.00</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>IGV (18 %):</span>
                        <strong id="igv-venta" data-resumen-valor="IGV (18 %)">S/ 0.00</strong>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-3">
                        <h4 class="mb-0">Total:</h4>
                        <h4 class="mb-0 text-danger" id="total-venta" data-resumen-valor="Total">S/ 0.00</h4>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-rojo"
                        {{ $productos->isEmpty() ? 'disabled' : '' }}>
                    {{ $boton }}
                </button>

                <a href="{{ $cancelar }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const contenedor = document.getElementById('productos-container');
    const plantilla = document.getElementById('plantilla-fila');
    const botonAgregar = document.getElementById('agregar-producto');

    const subtotalVenta = document.getElementById('subtotal-venta');
    const igvVenta = document.getElementById('igv-venta');
    const totalVenta = document.getElementById('total-venta');

    let siguienteIndice = {{ count($lineasIniciales) }};

    const porcentajeIGV = 0.18;

    function redondear(valor) {
        return Math.round((valor + Number.EPSILON) * 100) / 100;
    }

    function obtenerDatosFila(fila) {
        const select = fila.querySelector('.producto-select');
        const opcion = select.options[select.selectedIndex];

        // '' = todavía no se eligió si es por caja o por unidad.
        const modalidad = fila.querySelector('.modalidad-select').value;
        const porCaja = modalidad === 'caja';
        const cantidad = Number(fila.querySelector('.cantidad-input').value || 0);

        if (!opcion || !opcion.value) {
            return {
                subtotal: 0,
                insuficiente: false
            };
        }

        const precio = Number(opcion.dataset.precio || 0);
        const stock = Number(opcion.dataset.stock || 0);
        const unidadesPorCaja = Number(opcion.dataset.unidades || 0);

        // Por caja se multiplica por las unidades de la caja; por unidad se usa tal cual.
        const unidades = modalidad === ''
            ? 0
            : (porCaja ? cantidad * unidadesPorCaja : cantidad);
        const stockRestante = stock - unidades;

        return {
            modalidad: modalidad,
            porCaja: porCaja,
            precio: precio,
            stock: stock,
            unidadesPorCaja: unidadesPorCaja,
            cantidad: cantidad,
            unidades: unidades,
            stockRestante: stockRestante,
            // Igual que el servidor: cada línea se redondea a 2 decimales.
            subtotal: redondear(unidades * precio),
            insuficiente: unidades > 0 && (
                (porCaja && unidadesPorCaja <= 0) || stockRestante < 0
            )
        };
    }

    function actualizarFila(fila) {
        const select = fila.querySelector('.producto-select');
        const selectModalidad = fila.querySelector('.modalidad-select');
        const opcionCaja = selectModalidad.querySelector('option[value="caja"]');
        const cantidadInput = fila.querySelector('.cantidad-input');

        const etiquetaCantidad = fila.querySelector('.cantidad-etiqueta');
        const ayudaModalidad = fila.querySelector('.ayuda-modalidad');
        const disponible = fila.querySelector('.disponible');
        const unidadesTotal = fila.querySelector('.unidades-total');
        const stockRestante = fila.querySelector('.stock-restante');
        const subtotalFila = fila.querySelector('.subtotal-fila');
        const unidadesResumen = fila.querySelector('.unidades-resumen');

        const modalidad = selectModalidad.value;
        const porCaja = modalidad === 'caja';
        const opcion = select.options[select.selectedIndex];

        etiquetaCantidad.textContent = modalidad === ''
            ? 'Cantidad'
            : (porCaja ? 'Cajas' : 'Unidades');
        cantidadInput.placeholder = porCaja ? 'Ej: 2' : 'Ej: 25';

        stockRestante.classList.remove('text-danger', 'text-success');

        if (!opcion || !opcion.value) {
            opcionCaja.textContent = 'Por caja';
            ayudaModalidad.innerHTML = '&nbsp;';
            unidadesResumen.textContent = '';
            cantidadInput.removeAttribute('max');

            disponible.textContent = 'Selecciona un producto';
            unidadesTotal.textContent = '0';
            stockRestante.innerHTML = '&nbsp;';

            subtotalFila.textContent = 'S/ 0.00';

            return {
                subtotal: 0,
                insuficiente: false
            };
        }

        const datos = obtenerDatosFila(fila);

        const maxCajas = datos.unidadesPorCaja > 0
            ? Math.floor(datos.stock / datos.unidadesPorCaja)
            : 0;

        // La opción muestra cuántas unidades trae la caja de este producto.
        opcionCaja.textContent = 'Por caja (' + datos.unidadesPorCaja + ' u.)';

        disponible.textContent =
            'Disponible: ' + datos.stock + ' unidades (' +
            maxCajas + ' cajas completas)';

        if (modalidad === '') {
            ayudaModalidad.textContent = 'Elige cómo vender';
            cantidadInput.removeAttribute('max');
        } else {
            ayudaModalidad.textContent = porCaja
                ? 'Caja de ' + datos.unidadesPorCaja + ' u. · máx. ' + maxCajas
                : 'Máx. ' + datos.stock + ' unidades';

            cantidadInput.max = porCaja ? maxCajas : datos.stock;
        }

        unidadesTotal.textContent = datos.unidades;
        unidadesResumen.textContent = datos.unidades || '';

        stockRestante.textContent =
            'Quedan ' + datos.stockRestante + ' en stock';

        stockRestante.classList.add(
            datos.insuficiente ? 'text-danger' : 'text-success'
        );

        subtotalFila.textContent =
            'S/ ' + datos.subtotal.toFixed(2);

        return datos;
    }

    function actualizarTodo() {
        const filas = contenedor.querySelectorAll('.venta-fila');

        let subtotal = 0;

        filas.forEach(function (fila) {
            const datos = actualizarFila(fila);
            subtotal += datos.subtotal;

            fila.querySelector('.eliminar-fila').disabled =
                filas.length === 1;
        });

        subtotal = Math.round((subtotal + Number.EPSILON) * 100) / 100;

        const igv = Math.round(
            (subtotal * porcentajeIGV + Number.EPSILON) * 100
        ) / 100;

        const total = Math.round(
            (subtotal + igv + Number.EPSILON) * 100
        ) / 100;

        subtotalVenta.textContent = 'S/ ' + subtotal.toFixed(2);
        igvVenta.textContent = 'S/ ' + igv.toFixed(2);
        totalVenta.textContent = 'S/ ' + total.toFixed(2);
    }

    botonAgregar.addEventListener('click', function () {
        const html = plantilla.innerHTML.replaceAll(
            '__INDICE__',
            siguienteIndice
        );

        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;

        contenedor.appendChild(wrapper.firstElementChild);

        siguienteIndice++;

        actualizarTodo();
    });

    contenedor.addEventListener('click', function (event) {
        const boton = event.target.closest('.eliminar-fila');

        if (!boton || boton.disabled) {
            return;
        }

        boton.closest('.venta-fila').remove();

        actualizarTodo();
    });

    contenedor.addEventListener('change', actualizarTodo);
    contenedor.addEventListener('input', actualizarTodo);

    actualizarTodo();
});
</script>
