@extends('layouts.app')

@section('title', 'Proveedores')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="page-title">Proveedores</h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('proveedores.create') }}" class="btn btn-rojo">
            Nuevo Proveedor
        </a>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body">
        @if($proveedores->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Razón social</th>
                            <th>País de origen</th>
                            <th>Correo electrónico</th>
                            <th>WeChat</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($proveedores as $proveedor)
                            <tr>
                                <td><strong>{{ $proveedor->razon_social }}</strong></td>
                                <td>{{ $proveedor->pais_origen ?? '—' }}</td>
                                <td>
                                    @if($proveedor->correo_electronico)
                                        <a href="mailto:{{ $proveedor->correo_electronico }}">{{ $proveedor->correo_electronico }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $proveedor->identificador_wechat ?? '—' }}</td>
                                <td>
                                    @if($proveedor->activo)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-danger">Inactivo</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('proveedores.edit', $proveedor) }}" class="btn btn-sm btn-negro" title="Editar">Editar</a>
                                    <form action="{{ route('proveedores.destroy', $proveedor) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-rojo" onclick="return confirm('¿Está seguro?')" title="Eliminar">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $proveedores->links() }}
            </div>
        @else
            <p class="text-center text-muted py-4">No hay proveedores registrados.</p>
        @endif
    </div>
</div>
@endsection
