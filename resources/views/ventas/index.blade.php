@extends('layouts.app')

@section('title', 'Ventas')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="page-title">Ventas Registradas</h2>
    </div>

    <div class="col-md-4 text-end">
        <a href="{{ route('ventas.create') }}" class="btn btn-rojo">
            Registrar Venta
        </a>
    </div>
</div>

<div class="card card-custom mb-4">
    <div class="card-body">
        <h5>Total vendido (sin anuladas)</h5>
        <h2>S/ {{ number_format($totalVendido, 2) }}</h2>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body">
        @if($ventas->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Productos</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Registrado por</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($ventas as $venta)
                            <tr class="{{ $venta->estaAnulada() ? 'text-muted' : '' }}">
                                <td><strong>{{ $venta->numero }}</strong></td>

                                <td>{{ $venta->fecha_venta->format('d/m/Y') }}</td>

                                <td>{{ $venta->nombreCliente() }}</td>

                                <td>
                                    {{ $venta->detalles_count }}
                                    {{ $venta->detalles_count === 1 ? 'producto' : 'productos' }}
                                </td>

                                <td>
                                    <strong>S/ {{ number_format($venta->total, 2) }}</strong>
                                </td>

                                <td>
                                    @if($venta->estaAnulada())
                                        <span class="badge bg-secondary">Anulada</span>
                                    @else
                                        <span class="badge bg-success">Vigente</span>
                                    @endif
                                </td>

                                <td>{{ $venta->usuario->name }}</td>

                                <td>
                                    <a href="{{ route('ventas.show', $venta) }}"
                                       class="btn btn-sm btn-outline-dark">
                                        Ver detalle
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $ventas->links() }}
            </div>
        @else
            <p class="text-center text-muted py-4">
                Todavía no hay ventas registradas.
            </p>
        @endif
    </div>
</div>
@endsection
