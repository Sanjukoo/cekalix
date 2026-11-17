@if($productos->isEmpty())
    <div class="alert alert-warning">
        No hay productos disponibles para vender. Verifica que tengan stock, precio de venta y unidades por caja asignadas.
    </div>
@endif

@php
    $lineasVenta = $venta
        ? $venta->detalles->map(function ($d) {
            $unidadesPorCaja = (int) ($d->unidades_por_caja ?: $d->producto?->unidades_por_caja);

            $cajas = $d->cajas;

            // Para ventas antiguas, solo calculamos las cajas
            // cuando las unidades forman cajas completas.
            if ($cajas === null && $unidadesPorCaja > 0 && $d->cantidad % $unidadesPorCaja === 0) {
                $cajas = intdiv($d->cantidad, $unidadesPorCaja);
            }

            return [
                'producto_id' => $d->producto_id,
                'cajas' => $cajas ?? '',
            ];
        })->values()->all()
        : [['producto_id' => '', 'cajas' => '']];

    $lineasIniciales = old('productos', $lineasVenta);

    $ventaConLineasAntiguas = $venta
        && $venta->detalles->contains(function ($d) {
            $unidadesPorCaja = (int) ($d->unidades_por_caja ?: $d->producto?->unidades_por_caja);

            return $d->cajas === null
                && ($unidadesPorCaja <= 0 || $d->cantidad % $unidadesPorCaja !== 0);
        });
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

        @if($ventaConLineasAntiguas)
            <div class="alert alert-warning">
                Esta venta contiene detalles registrados anteriormente por unidades.
                Algunas cantidades no equivalen a cajas completas. Revisa y completa
                las cantidades en cajas antes de actualizar la venta.
            </div>
        @endif

        <form action="{{ $action }}" method="POST" id="formulario-venta">
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
                Ingresa la cantidad de cajas que deseas vender. El sistema calculará
                automáticamente las unidades y el stock restante. El stock solo se
                actualizará cuando registres la venta.
            </p>

            <div id="productos-container">
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
                        <strong id="subtotal-venta">S/ 0.00</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>IGV (18 %):</span>
                        <strong id="igv-venta">S/ 0.00</strong>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-3">
                        <h4 class="mb-0">Total:</h4>
                        <h4 class="mb-0 text-danger" id="total-venta">S/ 0.00</h4>
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

    function obtenerDatosFila(fila) {
        const select = fila.querySelector('.producto-select');
        const opcion = select.options[select.selectedIndex];

        const cajasInput = fila.querySelector('.cajas-input');

        if (!opcion || !opcion.value) {
            return {
                subtotal: 0,
                insuficiente: false
            };
        }

        const precio = Number(opcion.dataset.precio || 0);
        const stock = Number(opcion.dataset.stock || 0);
        const unidadesPorCaja = Number(opcion.dataset.unidades || 0);
        const cajas = Number(cajasInput.value || 0);

        const unidades = cajas * unidadesPorCaja;
        const stockRestante = stock - unidades;

        return {
            precio: precio,
            stock: stock,
            unidadesPorCaja: unidadesPorCaja,
            cajas: cajas,
            unidades: unidades,
            stockRestante: stockRestante,
            subtotal: unidades * precio,
            insuficiente: cajas > 0 && (
                unidadesPorCaja <= 0 || stockRestante < 0
            )
        };
    }

    function actualizarFila(fila) {
        const select = fila.querySelector('.producto-select');
        const cajasInput = fila.querySelector('.cajas-input');

        const disponible = fila.querySelector('.disponible');
        const unidadesTotal = fila.querySelector('.unidades-total');
        const stockRestante = fila.querySelector('.stock-restante');
        const subtotalFila = fila.querySelector('.subtotal-fila');

        const opcion = select.options[select.selectedIndex];

        if (!opcion || !opcion.value) {
            cajasInput.removeAttribute('max');

            disponible.textContent = 'Selecciona un producto';
            unidadesTotal.textContent = 'Unidades: 0';
            stockRestante.textContent = 'Stock restante: 0';

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

        if (datos.unidadesPorCaja > 0) {
            cajasInput.max = maxCajas;
        } else {
            cajasInput.removeAttribute('max');
        }

        disponible.textContent =
            'Disponible: ' + datos.stock + ' unidades (' +
            maxCajas + ' cajas completas)';

        unidadesTotal.textContent =
            'Unidades a vender: ' + datos.unidades;

        stockRestante.textContent =
            'Stock restante: ' + datos.stockRestante + ' unidades';

        stockRestante.classList.toggle(
            'text-danger',
            datos.insuficiente
        );

        stockRestante.classList.toggle(
            'text-success',
            !datos.insuficiente
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
