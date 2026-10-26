@extends('layouts.app')

@section('title', 'Editar Cliente')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2 class="page-title">Editar Cliente</h2>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body">
        <form action="{{ route('clientes.update', $cliente) }}" method="POST">
            @csrf
            @method('PUT')

            @include('clientes._campos')

            <button type="submit" class="btn btn-rojo">Actualizar cliente</button>
            <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </form>
    </div>
</div>
@endsection
