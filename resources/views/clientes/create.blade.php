@extends('layouts.app')

@section('title', 'Nuevo Cliente')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2 class="page-title">Nuevo Cliente</h2>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body">
        <form action="{{ route('clientes.store') }}" method="POST">
            @csrf

            @include('clientes._campos')

            <button type="submit" class="btn btn-rojo">Guardar cliente</button>
            <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </form>
    </div>
</div>
@endsection
