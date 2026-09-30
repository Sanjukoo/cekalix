@extends('layouts.app')

@section('title', 'Administración')

@section('content')
    <div class="mb-4">
        <h1 class="h3">Panel de administración</h1>
        <p class="text-muted">
            Bienvenido, {{ auth()->user()->name }}.
            Gestiona proveedores e importaciones.
        </p>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div class="card card-custom h-100">
                <div class="card-body">
                    <h2 class="h5">Proveedores activos</h2>

                    <p class="display-6">
                        {{ $totalProveedores }}
                    </p>

                    <a href="{{ route('proveedores.index') }}"
                       class="btn btn-negro">
                        Ver proveedores
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card card-custom h-100">
                <div class="card-body">
                    <h2 class="h5">Importaciones registradas</h2>

                    <p class="display-6">
                        {{ $totalImportaciones }}
                    </p>

                    <a href="{{ route('importaciones.index') }}"
                       class="btn btn-negro">
                        Ver importaciones
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection