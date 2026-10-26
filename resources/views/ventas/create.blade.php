@extends('layouts.app')

@section('title', 'Registrar Venta')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2 class="page-title">Registrar Venta</h2>
    </div>
</div>

@include('ventas._formulario', [
    'venta' => null,
    'action' => route('ventas.store'),
    'boton' => 'Registrar Venta',
    'cancelar' => route('ventas.index'),
])
@endsection
