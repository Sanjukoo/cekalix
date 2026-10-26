@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
@include('layouts._hub', ['hubActivo' => 'socios', 'activa' => 'clientes'])

<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="page-title">Clientes</h2>
    </div>

    <div class="col-md-4 text-end">
        <a href="{{ route('clientes.create') }}" class="btn btn-rojo">Nuevo Cliente</a>
    </div>
</div>

<div class="card card-custom mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('clientes.index') }}" class="row g-2">
            <div class="col-md-10">
                <input type="text" name="buscar" class="form-control"
                       placeholder="Buscar por nombre o número de documento"
                       value="{{ $buscar }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-negro w-100">Buscar</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body">
        @if($clientes->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Documento</th>
                            <th>Teléfono</th>
                            <th>Dirección</th>
                            <th>Ventas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($clientes as $cliente)
                            <tr>
                                <td><strong>{{ $cliente->nombre }}</strong></td>
                                <td>
                                    @if($cliente->numero_documento)
                                        {{ $cliente->tipo_documento }} {{ $cliente->numero_documento }}
                                    @else
                                        <span class="text-muted">Sin documento</span>
                                    @endif
                                </td>
                                <td>{{ $cliente->telefono ?? '—' }}</td>
                                <td>{{ $cliente->direccion ?? '—' }}</td>
                                <td>{{ $cliente->ventas_count }}</td>
                                <td>
                                    <div class="acciones-tabla">
                                        <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-sm btn-primary">Editar</a>

                                        <form action="{{ route('clientes.destroy', $cliente) }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar este cliente?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $clientes->links() }}
            </div>
        @else
            <p class="text-center text-muted py-4">
                No se encontraron clientes.
            </p>
        @endif
    </div>
</div>
@endsection
