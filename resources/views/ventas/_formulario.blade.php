@if($productos->isEmpty())
    <div class="alert alert-warning">
        No hay productos disponibles para vender. Verifica que tengan stock y precio de venta asignado.
    </div>
@endif

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

        <form action="{{ $action }}" method="POST">
            @csrf
            @if(isset($venta))
                @method('PUT')
            @endif

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="cliente_id" class="form-label">Cliente</label>

                    <select id="cliente_id" name="cliente_id" class="form-select @error('cliente_id') is-invalid @enderror">
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
                           value="{{ old('fecha_venta', $venta?->fecha_venta?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>

                    @error('fecha_venta')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <hr>

            <h6 class="text-danger mb-3">Productos Vendidos</h6>

            <p class="text-muted">
                Selecciona los productos y la cantidad en unidades. El stock disponible se descuenta al registrar la venta.
            </p>

            @php
                $lineasIniciales = old('productos', $venta
                    ? $venta->detalles->map(fn ($d) => ['producto_id' => $d->producto_id, 'cantidad' => $d->cantidad])->values()->all()
                    : [['producto_id' => '', 'cantidad' => '']]);
            @endphp

            <div id="productos-container">
                @foreach($lineasIniciales as $i => $linea)
                    @include('ventas._fila', [
                        'indice' => $i,
                        'productos' => $productos,
                        'linea' => $linea,
                    ])
                @endforeach
            </div>

            {{-- Plantilla para agregar filas sin repetir el listado de productos --}}
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

            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    Total: <span id="total-venta">S/ 0.00</span>
                </h4>

                <div>
                    <button type="submit" class="btn btn-rojo"
                            {{ $productos->isEmpty() ? 'disabled' : '' }}>
                        {{ $boton }}
                    </button>

                    <a href="{{ $cancelar }}" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const contenedor = document.getElementById('productos-container');
    const plantilla = document.getElementById('plantilla-fila');
    const botonAgregar = document.getElementById('agregar-producto');
    const totalVenta = document.getElementById('total-venta');

    let siguienteIndice = {{ count($lineasIniciales) }};

    function subtotalDeFila(fila) {
        const select = fila.querySelector('.producto-select');
        const opcion = select.options[select.selectedIndex];

        if (!opcion || !opcion.value) {
            return 0;
        }

        const precio = Number(opcion.dataset.precio || 0);
        const cantidad = Number(fila.querySelector('.cantidad-input').value || 0);

        return precio * cantidad;
    }

    function actualizarFila(fila) {
        const select = fila.querySelector('.producto-select');
        const cantidadInput = fila.querySelector('.cantidad-input');
        const disponible = fila.querySelector('.disponible');
        const opcion = select.options[select.selectedIndex];

        if (!opcion || !opcion.value) {
            cantidadInput.removeAttribute('max');
            disponible.textContent = '';
        } else {
            // El límite lo muestra el navegador; el servidor vuelve a validarlo.
            cantidadInput.max = opcion.dataset.stock;
            disponible.textContent = 'Disponible: ' + opcion.dataset.stock + ' unidades';
        }

        fila.querySelector('.subtotal-fila').textContent =
            'S/ ' + subtotalDeFila(fila).toFixed(2);
    }

    function actualizarTodo() {
        const filas = contenedor.querySelectorAll('.venta-fila');
        let total = 0;

        filas.forEach(function (fila) {
            actualizarFila(fila);
            total += subtotalDeFila(fila);
        });

        totalVenta.textContent = 'S/ ' + total.toFixed(2);

        filas.forEach(function (fila) {
            fila.querySelector('.eliminar-fila').disabled = filas.length === 1;
        });
    }

    botonAgregar.addEventListener('click', function () {
        const html = plantilla.innerHTML.replaceAll('__INDICE__', siguienteIndice);
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
