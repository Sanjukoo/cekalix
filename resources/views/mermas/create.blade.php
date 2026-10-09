@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Registrar cajas dañadas</h2>

    <div class="card">
        <div class="card-body">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('mermas.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="importacion_detalle_id" class="form-label">
                        Importación y producto
                    </label>

                    <select
                        name="importacion_detalle_id"
                        id="importacion_detalle_id"
                        class="form-select"
                        required
                    >
                        <option value="">Seleccione una importación y producto</option>

                        @foreach ($importaciones as $importacion)
                            @foreach ($importacion->detalles as $detalle)
                                <option
                                    value="{{ $detalle->id }}"
                                    {{ old('importacion_detalle_id') == $detalle->id ? 'selected' : '' }}
                                >
                                    Importación #{{ $importacion->id }}
                                    - Contenedor {{ $importacion->numero_contenedor }}
                                    - {{ $detalle->producto->nombre }}
                                    - {{ $detalle->cajas_facturadas }} cajas facturadas
                                </option>
                            @endforeach
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="cantidad" class="form-label">
                        Cantidad de cajas dañadas
                    </label>

                    <input
                        type="number"
                        name="cantidad"
                        id="cantidad"
                        class="form-control"
                        min="1"
                        value="{{ old('cantidad') }}"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="motivo" class="form-label">
                        Motivo del daño
                    </label>

                    <select
                        name="motivo"
                        id="motivo"
                        class="form-select"
                        required
                    >
                        <option value="">Seleccione el motivo</option>

                        <option value="Cajas aplastadas"
                            {{ old('motivo') == 'Cajas aplastadas' ? 'selected' : '' }}>
                            Cajas aplastadas
                        </option>

                        <option value="Cajas rotas"
                            {{ old('motivo') == 'Cajas rotas' ? 'selected' : '' }}>
                            Cajas rotas
                        </option>

                        <option value="Cajas mojadas"
                            {{ old('motivo') == 'Cajas mojadas' ? 'selected' : '' }}>
                            Cajas mojadas
                        </option>

                        <option value="Mercadería destruida"
                            {{ old('motivo') == 'Mercadería destruida' ? 'selected' : '' }}>
                            Mercadería destruida
                        </option>

                        <option value="Otro"
                            {{ old('motivo') == 'Otro' ? 'selected' : '' }}>
                            Otro
                        </option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="fecha_registro" class="form-label">
                        Fecha del registro
                    </label>

                    <input
                        type="date"
                        name="fecha_registro"
                        id="fecha_registro"
                        class="form-control"
                        value="{{ old('fecha_registro', now()->format('Y-m-d')) }}"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-rojo">
                    Guardar merma
                </button>

                <a href="{{ route('mermas.index') }}"
                   class="btn btn-secondary">
                    Cancelar
                </a>
            </form>
        </div>
    </div>
</div>
@endsection