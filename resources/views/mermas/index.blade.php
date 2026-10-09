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
                            <th>Producto</th>
                            <th>Cajas dañadas</th>
                            <th>Motivo</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($mermas as $merma)
                            <tr>
                                <td>
                                    #{{ $merma->detalleImportacion->importacion->id }}
                                </td>

                                <td>
                                    {{ $merma->detalleImportacion->importacion->numero_contenedor }}
                                </td>

                                <td>
                                    {{ $merma->detalleImportacion->producto->nombre }}
                                </td>

                                <td>{{ $merma->cantidad }}</td>

                                <td>{{ $merma->motivo }}</td>

                                <td>
                                    {{ $merma->fecha_registro->format('d/m/Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">
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