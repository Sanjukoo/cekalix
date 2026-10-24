@extends('layouts.app')

@section('title', 'Nueva Importación')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2 class="page-title">Nueva Importación</h2>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header">Datos de la Importación</div>
    <div class="card-body">
        <form action="{{ route('importaciones.store') }}" method="POST">
            @csrf

            <h6 class="text-danger mb-3">Datos Generales</h6>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="proveedor_id" class="form-label">
                        Proveedor <span class="text-danger">*</span>
                    </label>

                    <select id="proveedor_id" name="proveedor_id"
                            class="form-select @error('proveedor_id') is-invalid @enderror" required>
                        <option value="">-- Seleccionar proveedor registrado --</option>
                        @foreach($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}"
                                @selected(old('proveedor_id') == $proveedor->id)>
                                {{ $proveedor->razon_social }}
                            </option>
                        @endforeach
                    </select>

                    @error('proveedor_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="fecha_llegada" class="form-label">
                        Fecha de Llegada <span class="text-danger">*</span>
                    </label>

                    <input type="date" id="fecha_llegada" name="fecha_llegada"
                           class="form-control @error('fecha_llegada') is-invalid @enderror"
                           value="{{ old('fecha_llegada') }}" required>

                    @error('fecha_llegada')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="numero_factura" class="form-label">
                        Nº de Factura <span class="text-danger">*</span>
                    </label>

                    <input type="text" id="numero_factura" name="numero_factura"
                           class="form-control @error('numero_factura') is-invalid @enderror"
                           placeholder="Ej: FAC-00125"
                           value="{{ old('numero_factura') }}" required>

                    @error('numero_factura')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="numero_contenedor" class="form-label">
                        Nº de Contenedor <span class="text-danger">*</span>
                    </label>

                    <input type="text" id="numero_contenedor" name="numero_contenedor"
                           class="form-control @error('numero_contenedor') is-invalid @enderror"
                           placeholder="Ej: CONT-001"
                           value="{{ old('numero_contenedor') }}" required>

                    @error('numero_contenedor')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <hr>

            <h6 class="text-danger mb-3">Detalle de Mercadería</h6>

            <p class="text-muted">
                Selecciona un SKU existente o escribe uno nuevo. Registra las cajas
                facturadas de cada producto.
            </p>

            <datalist id="lista-skus">
                @foreach($productos as $producto)
                    <option value="{{ $producto->codigo }}">
                        {{ $producto->nombre }}
                    </option>
                @endforeach

                @foreach($codigosPendientes as $codigo)
                    <option value="{{ $codigo }}">
                        SKU pendiente de registro en Inventario
                    </option>
                @endforeach
            </datalist>

            @php
                $detallesIniciales = old('detalles', [
                    ['producto_codigo' => '', 'cajas_facturadas' => '']
                ]);
            @endphp

            <div id="detalles-container">
                @foreach($detallesIniciales as $i => $detalle)
                    <div class="row mb-3 detalle-row align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">
                                SKU / Código <span class="text-danger">*</span>
                            </label>

                            <input type="text"
                                   name="detalles[{{ $i }}][producto_codigo]"
                                   class="form-control"
                                   list="lista-skus"
                                   maxlength="50"
                                   placeholder="Seleccionar o escribir SKU"
                                   value="{{ $detalle['producto_codigo'] ?? '' }}"
                                   required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                Cajas Facturadas <span class="text-danger">*</span>
                            </label>

                            <input type="number"
                                   name="detalles[{{ $i }}][cajas_facturadas]"
                                   class="form-control"
                                   min="1"
                                   step="1"
                                   placeholder="Ej: 50"
                                   value="{{ $detalle['cajas_facturadas'] ?? '' }}"
                                   required>
                        </div>

                        <div class="col-md-2">
                            <button type="button"
                                    class="btn btn-outline-danger w-100 eliminar-detalle"
                                    {{ count($detallesIniciales) === 1 ? 'disabled' : '' }}>
                                Quitar
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            @error('detalles')
                <div class="text-danger mb-3">{{ $message }}</div>
            @enderror

            @if($errors->has('detalles.*.producto_codigo') ||
                $errors->has('detalles.*.cajas_facturadas'))
                <div class="alert alert-danger">
                    Revisa los SKU y las cajas facturadas de cada fila.
                </div>
            @endif

            <button type="button" id="agregar-detalle"
                    class="btn btn-outline-secondary mb-3">
                + Agregar otro producto
            </button>

            <hr>

            <div class="mt-4">
                <button type="submit" class="btn btn-rojo">
                    Registrar Importación
                </button>

                <a href="{{ route('importaciones.index') }}"
                   class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('detalles-container');
    const botonAgregar = document.getElementById('agregar-detalle');

    let siguienteIndice = {{ count($detallesIniciales) }};

    function actualizarBotonesEliminar() {
        const filas = container.querySelectorAll('.detalle-row');

        filas.forEach(function (fila) {
            const boton = fila.querySelector('.eliminar-detalle');
            boton.disabled = filas.length === 1;
        });
    }

    botonAgregar.addEventListener('click', function () {
        const fila = document.createElement('div');

        fila.className = 'row mb-3 detalle-row align-items-end';

        fila.innerHTML = `
            <div class="col-md-6">
                <label class="form-label">
                    SKU / Código <span class="text-danger">*</span>
                </label>
                <input type="text"
                       name="detalles[${siguienteIndice}][producto_codigo]"
                       class="form-control"
                       list="lista-skus"
                       maxlength="50"
                       placeholder="Seleccionar o escribir SKU"
                       required>
            </div>

            <div class="col-md-4">
                <label class="form-label">
                    Cajas Facturadas <span class="text-danger">*</span>
                </label>
                <input type="number"
                       name="detalles[${siguienteIndice}][cajas_facturadas]"
                       class="form-control"
                       min="1"
                       step="1"
                       placeholder="Ej: 50"
                       required>
            </div>

            <div class="col-md-2">
                <button type="button"
                        class="btn btn-outline-danger w-100 eliminar-detalle">
                    Quitar
                </button>
            </div>
        `;

        container.appendChild(fila);
        siguienteIndice++;

        actualizarBotonesEliminar();
    });

    container.addEventListener('click', function (event) {
        const boton = event.target.closest('.eliminar-detalle');

        if (!boton || boton.disabled) {
            return;
        }

        boton.closest('.detalle-row').remove();
        actualizarBotonesEliminar();
    });

    actualizarBotonesEliminar();
});
</script>
@endsection
