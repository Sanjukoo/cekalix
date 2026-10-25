@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Registro de mermas</h2>

        <a href="{{ route('mermas.create') }}" class="btn btn-rojo">
            Registrar merma
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('cajasEstables'))
        <div class="alert alert-info">
            <strong>Resultado:</strong>
            {{ session('cajasEstables') }} cajas estables
            en el detalle de importación seleccionado.
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <h5>Total de cajas dañadas registradas</h5>
            <h2>{{ $totalCajasDaniadas }} cajas</h2>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Historial de mermas
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Importación</th>
                            <th>Contenedor</th>
                            <th>Código SKU</th>
                            <th>Producto</th>
                            <th>Cajas facturadas</th>
                            <th>Cajas recibidas</th>
                            <th>Cajas dañadas</th>
                            <th>Cajas estables</th>
                            <th>Motivo</th>
                            <th>Descripción</th>
                            <th>Fecha</th>

                            @if (auth()->user()->role === 'admin')
                                <th>Acciones</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($mermas as $merma)
                            @php
                                $detalle = $merma->detalleImportacion;

                                $totalDanadas = $detalle
                                    ? $detalle->mermas->sum('cantidad')
                                    : 0;

                                $cajasFacturadas = $detalle
                                    ? $detalle->cajas_facturadas
                                    : 0;

                                $cajasRecibidas = $detalle?->conteoFisico?->cajas_contadas;

                                $cajasEstables = max(
                                    0,
                                    ($cajasRecibidas ?? 0) - $totalDanadas
                                );

                                $editable = $detalle?->importacion?->estado === 'En recepción';
                            @endphp

                            <tr>
                                <td>
                                    #{{ $detalle->importacion->id ?? 'N/A' }}
                                </td>

                                <td>
                                    {{ $detalle->importacion->numero_contenedor ?? 'N/A' }}
                                </td>

                                <td>
                                    {{ $detalle->producto_codigo ?? $detalle->producto?->codigo ?? 'N/A' }}
                                </td>

                                <td>
                                    {{ $detalle->producto?->nombre ?? 'Pendiente de registro' }}
                                </td>

                                <td>{{ $cajasFacturadas }}</td>

                                <td>{{ $cajasRecibidas ?? 'Sin contar' }}</td>

                                <td>{{ $merma->cantidad }}</td>

                                <td>{{ $cajasEstables }}</td>

                                <td>
                                    {{ $merma->motivo === 'OTRO' ? 'Otro' : $merma->motivo }}
                                </td>

                                <td>
                                    @if ($merma->motivo === 'OTRO')
                                        {{ $merma->descripcion ?? 'Sin descripción' }}
                                    @else
                                        —
                                    @endif
                                </td>

                                <td>
                                    {{ $merma->fecha_registro->format('d/m/Y') }}
                                </td>

                                @if (auth()->user()->role === 'admin')
                                    <td>
                                        @if (!$editable)
                                            <span class="text-muted small">Importación cerrada</span>
                                        @else
                                        <div class="acciones-tabla">
                                            <a
                                                href="{{ route('mermas.edit', $merma) }}"
                                                class="btn btn-sm btn-primary"
                                            >
                                                Editar
                                            </a>

                                            <form
                                                action="{{ route('mermas.destroy', $merma) }}"
                                                method="POST"
                                                onsubmit="return confirm('¿Seguro que deseas eliminar esta merma?');"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-danger"
                                                >
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="{{ auth()->user()->role === 'admin' ? 12 : 11 }}"
                                    class="text-center"
                                >
                                    Todavía no hay mermas registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $mermas->links() }}
        </div>
    </div>
</div>
@endsection
