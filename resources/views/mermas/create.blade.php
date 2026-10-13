@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Registrar cajas dañadas</h2>

    <div class="card">
        <div class="card-body">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('mermas.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="importacion_detalle_id" class="form-label">
                        Factura / Importación y código SKU
                    </label>

                    <select
                        name="importacion_detalle_id"
                        id="importacion_detalle_id"
                        class="form-select"
                        required
                    >
                        <option value="">Seleccione una importación y producto</option>

                        @foreach ($importaciones as $importacion)
                            @foreach ($importacion->detalles as $detalle)
                                <option
                                    value="{{ $detalle->id }}"
                                    data-cajas="{{ $detalle->cajas_facturadas }}"
                                    data-mermas="{{ $detalle->mermas->sum('cantidad') }}"
                                    {{ old('importacion_detalle_id', request('importacion_detalle_id')) == $detalle->id ? 'selected' : '' }}
                                >
                                    Importación #{{ $importacion->id }}
                                    - Contenedor {{ $importacion->numero_contenedor }}
                                    - SKU: {{ $detalle->producto->codigo ?? 'Sin código' }}
                                    - {{ $detalle->producto->nombre ?? 'Producto sin nombre' }}
                                    - {{ $detalle->cajas_facturadas }} cajas facturadas
                                </option>
                            @endforeach
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="cantidad" class="form-label">
                        Cantidad de cajas dañadas
                    </label>

                    <input
                        type="number"
                        name="cantidad"
                        id="cantidad"
                        class="form-control"
                        min="1"
                        step="1"
                        value="{{ old('cantidad') }}"
                        required
                    >

                    <div class="form-text">
                        La cantidad acumulada no puede superar las cajas facturadas.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="motivo" class="form-label">
                        Motivo del daño
                    </label>

                    <select
                        name="motivo"
                        id="motivo"
                        class="form-select"
                        required
                    >
                        <option value="">Seleccione el motivo</option>

                        <option value="Cajas aplastadas"
                            {{ old('motivo') == 'Cajas aplastadas' ? 'selected' : '' }}>
                            Cajas aplastadas
                        </option>

                        <option value="Cajas rotas"
                            {{ old('motivo') == 'Cajas rotas' ? 'selected' : '' }}>
                            Cajas rotas
                        </option>

                        <option value="Cajas mojadas"
                            {{ old('motivo') == 'Cajas mojadas' ? 'selected' : '' }}>
                            Cajas mojadas
                        </option>

                        <option value="Mercadería destruida"
                            {{ old('motivo') == 'Mercadería destruida' ? 'selected' : '' }}>
                            Mercadería destruida
                        </option>

                        <option value="OTRO"
                            {{ old('motivo') == 'OTRO' ? 'selected' : '' }}>
                            Otro
                        </option>
                    </select>
                </div>

                <div
                    class="mb-3"
                    id="contenedor_descripcion"
                    style="{{ old('motivo') == 'OTRO' ? '' : 'display: none;' }}"
                >
                    <label for="descripcion" class="form-label">
                        Descripción del motivo
                    </label>

                    <textarea
                        name="descripcion"
                        id="descripcion"
                        class="form-control"
                        rows="3"
                        maxlength="1000"
                        placeholder="Describa el motivo de la merma"
                        {{ old('motivo') == 'OTRO' ? 'required' : '' }}
                    >{{ old('descripcion') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="fecha_registro" class="form-label">
                        Fecha del registro
                    </label>

                    <input
                        type="date"
                        name="fecha_registro"
                        id="fecha_registro"
                        class="form-control"
                        value="{{ old('fecha_registro', now()->format('Y-m-d')) }}"
                        required
                    >
                </div>

                <div class="alert alert-info" id="resumen_cajas" style="display: none;">
                    <p class="mb-1">
                        Cajas facturadas:
                        <strong id="cajas_facturadas">0</strong>
                    </p>

                    <p class="mb-1">
                        Mermas registradas anteriormente:
                        <strong id="mermas_anteriores">0</strong>
                    </p>

                    <p class="mb-1">
                        Cajas dañadas incluyendo este registro:
                        <strong id="total_daniadas">0</strong>
                    </p>

                    <p class="mb-0">
                        Cajas estables estimadas:
                        <strong id="cajas_estables">0</strong>
                    </p>
                </div>

                <button type="submit" class="btn btn-rojo">
                    Guardar merma
                </button>

                <a href="{{ route('mermas.index') }}"
                   class="btn btn-secondary">
                    Cancelar
                </a>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const motivo = document.getElementById('motivo');
    const contenedorDescripcion = document.getElementById('contenedor_descripcion');
    const descripcion = document.getElementById('descripcion');

    const detalleSelect = document.getElementById('importacion_detalle_id');
    const cantidadInput = document.getElementById('cantidad');

    const resumen = document.getElementById('resumen_cajas');
    const cajasFacturadas = document.getElementById('cajas_facturadas');
    const mermasAnteriores = document.getElementById('mermas_anteriores');
    const totalDanadas = document.getElementById('total_daniadas');
    const cajasEstables = document.getElementById('cajas_estables');

    function actualizarDescripcion() {
        const mostrar = motivo.value === 'OTRO';

        contenedorDescripcion.style.display = mostrar ? 'block' : 'none';
        descripcion.required = mostrar;

        if (!mostrar) {
            descripcion.value = '';
        }
    }

    function actualizarResumen() {
        const opcion = detalleSelect.options[detalleSelect.selectedIndex];

        if (!opcion || !opcion.value) {
            resumen.style.display = 'none';
            return;
        }

        const facturadas = Number(opcion.dataset.cajas || 0);
        const anteriores = Number(opcion.dataset.mermas || 0);
        const cantidad = Number(cantidadInput.value || 0);
        const total = anteriores + cantidad;
        const estables = facturadas - total;

        cajasFacturadas.textContent = facturadas;
        mermasAnteriores.textContent = anteriores;
        totalDanadas.textContent = total;
        cajasEstables.textContent = estables;

        resumen.classList.toggle('alert-danger', estables < 0);
        resumen.classList.toggle('alert-info', estables >= 0);

        resumen.style.display = 'block';
    }

    motivo.addEventListener('change', actualizarDescripcion);
    detalleSelect.addEventListener('change', actualizarResumen);
    cantidadInput.addEventListener('input', actualizarResumen);

    actualizarDescripcion();
    actualizarResumen();
});
</script>
@endsection
