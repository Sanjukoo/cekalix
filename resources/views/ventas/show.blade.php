@extends('layouts.app')

@section('title', 'Venta ' . $venta->numero)

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h2 class="page-title">
            {{ $venta->numero }}
            @if($venta->estaAnulada())
                <span class="badge bg-secondary">Anulada</span>
            @endif
        </h2>
    </div>

    <div class="col-md-6 text-end">
        <a href="{{ route('ventas.comprobante', $venta) }}" class="btn btn-negro">
            Descargar comprobante (PDF)
        </a>

        @unless($venta->estaAnulada())
            <a href="{{ route('ventas.edit', $venta) }}" class="btn btn-outline-dark">
                Editar
            </a>
        @endunless

        <a href="{{ route('ventas.index') }}" class="btn btn-outline-secondary">
            Volver
        </a>
    </div>
</div>

@if($venta->estaAnulada())
    <div class="alert alert-secondary">
        <strong>Venta anulada</strong>
        el {{ $venta->updated_at->format('d/m/Y H:i') }}
        por {{ $venta->anuladoPor?->name ?? 'usuario desconocido' }}.
        Motivo: {{ $venta->motivo_anulacion }}
    </div>
@endif

@error('motivo_anulacion')
    <div class="alert alert-danger">{{ $message }}</div>
@enderror

@error('productos')
    <div class="alert alert-danger">{{ $message }}</div>
@enderror

<div class="card card-custom mb-4">
    <div class="card-header">Datos de la venta</div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label"><strong>Fecha</strong></label>
                <p class="form-control-plaintext">
                    {{ $venta->fecha_venta->format('d/m/Y') }}
                </p>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label"><strong>Cliente</strong></label>
                <p class="form-control-plaintext">
                    {{ $venta->nombreCliente() }}

                    @if($venta->cliente?->numero_documento)
                        <small class="d-block text-muted">
                            {{ $venta->cliente->tipo_documento }}
                            {{ $venta->cliente->numero_documento }}
                        </small>
                    @endif
                </p>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label"><strong>Registrado por</strong></label>
                <p class="form-control-plaintext">
                    {{ $venta->usuario?->name ?? 'Usuario desconocido' }}
                </p>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label"><strong>Total con IGV</strong></label>
                <p class="form-control-plaintext text-danger">
                    <strong>S/ {{ number_format($venta->total, 2) }}</strong>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="card card-custom mb-4">
    <div class="card-header">Productos vendidos</div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Código SKU</th>
                        <th>Producto</th>
                        <th>Vendido por</th>
                        <th>Cantidad</th>
                        <th>Total unidades</th>
                        <th>Precio unitario</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($venta->detalles as $detalle)
                        <tr>
                            <td>
                                <span class="badge-sku">
                                    {{ $detalle->producto?->codigo ?? 'N/A' }}
                                </span>
                            </td>

                            <td>{{ $detalle->producto?->nombre ?? 'Producto no disponible' }}</td>

                            <td>{{ $detalle->descripcionModalidad() }}</td>

                            <td>{{ $detalle->descripcionCantidad() }}</td>

                            <td>{{ $detalle->cantidad }}</td>

                            <td>
                                S/ {{ number_format($detalle->precio_unitario, 2) }}
                            </td>

                            <td>
                                S/ {{ number_format($detalle->subtotal, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="row justify-content-end">
            <div class="col-md-5">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <strong>S/ {{ number_format($venta->subtotal, 2) }}</strong>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span>IGV (18 %):</span>
                    <strong>S/ {{ number_format($venta->igv, 2) }}</strong>
                </div>

                <hr>

                <div class="d-flex justify-content-between">
                    <h5>Total:</h5>
                    <h5 class="text-danger">
                        S/ {{ number_format($venta->total, 2) }}
                    </h5>
                </div>
            </div>
        </div>
    </div>
</div>

@unless($venta->estaAnulada())
    <div class="card card-custom border-danger">
        <div class="card-header text-danger">Anular venta</div>

        <div class="card-body">
            <p class="text-muted">
                Anular no borra la venta: queda registrada como anulada
                y el stock vuelve al inventario.
            </p>

            <form action="{{ route('ventas.anular', $venta) }}"
                  method="POST"
                  data-confirm="¿Anular esta venta? El stock se devolverá al inventario y la venta quedará registrada como anulada."
                  data-confirm-titulo="Confirmar anulación de venta"
                  data-confirm-boton="Anular venta"
                  data-confirm-datos="{{ json_encode([
                      'Venta' => $venta->numero,
                      'Cliente' => $venta->nombreCliente(),
                      'Fecha' => $venta->fecha_venta->format('d/m/Y'),
                      'Total' => 'S/ '.number_format($venta->total, 2),
                  ]) }}">
                @csrf
                @method('PATCH')

                <div class="row g-2 align-items-end">
                    <div class="col-md-8">
                        <label for="motivo_anulacion" class="form-label">
                            Motivo de la anulación
                        </label>

                        <input type="text" id="motivo_anulacion"
                               name="motivo_anulacion" maxlength="255"
                               class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <button type="submit" class="btn btn-outline-rojo w-100">
                            Anular venta
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endunless
@endsection
