@extends('layouts.app')

@section('title', 'Editar Venta')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2 class="page-title">Editar Venta {{ $venta->numero }}</h2>
        <p class="text-muted mb-0">
            Al guardar, el stock de las líneas anteriores se devuelve y se descuenta el de las nuevas.
        </p>
    </div>
</div>

@include('ventas._formulario', [
    'action' => route('ventas.update', $venta),
    'boton' => 'Guardar cambios',
    'cancelar' => route('ventas.show', $venta),
])
@endsection
