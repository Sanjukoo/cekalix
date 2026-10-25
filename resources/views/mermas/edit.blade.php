
@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Editar merma #{{ $merma->id }}</h2>

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

            <form action="{{ route('mermas.update', $merma) }}" method="POST">
                @csrf
                @method('PUT')

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
                                @continue(!$detalle->conteoFisico)
                                <option
                                    value="{{ $detalle->id }}"
                                    {{ old('importacion_detalle_id', $merma->importacion_detalle_id) == $detalle->id ? 'selected' : '' }}
                                >
                                    Importación #{{ $importacion->id }}
                                    - Contenedor {{ $importacion->numero_contenedor }}
                                    - {{ $detalle->producto_codigo ?? $detalle->producto?->codigo ?? 'Sin código' }}
                                    - {{ $detalle->producto?->nombre ?? 'Pendiente de registro' }}
                                    - {{ $detalle->conteoFisico->cajas_contadas }} cajas recibidas
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
                        value="{{ old('cantidad', $merma->cantidad) }}"
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

                        @foreach ([
                            'Cajas aplastadas',
                            'Cajas rotas',
                            'Cajas mojadas',
                            'Mercadería destruida',
                            'OTRO'
                        ] as $motivo)
                            <option
                                value="{{ $motivo }}"
                                {{ old('motivo', $merma->motivo) == $motivo ? 'selected' : '' }}
                            >
                                {{ $motivo === 'OTRO' ? 'Otro' : $motivo }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div
                    class="mb-3"
                    id="descripcion-container"
                    style="{{ old('motivo', $merma->motivo) === 'OTRO' ? '' : 'display: none;' }}"
                >
                    <label for="descripcion" class="form-label">
                        Descripción del motivo
                    </label>

                    <textarea
                        name="descripcion"
                        id="descripcion"
                        class="form-control"
                        rows="3"
                        maxlength="1000"
                    >{{ old('descripcion', $merma->descripcion) }}</textarea>
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
                        value="{{ old('fecha_registro', $merma->fecha_registro->format('Y-m-d')) }}"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-rojo">
                    Guardar cambios
                </button>

                <a href="{{ route('mermas.index') }}"
                   class="btn btn-secondary">
                    Cancelar
                </a>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const motivo = document.getElementById('motivo');
        const descripcionContainer = document.getElementById('descripcion-container');
        const descripcion = document.getElementById('descripcion');

        function actualizarDescripcion() {
            const mostrar = motivo.value === 'OTRO';

            descripcionContainer.style.display = mostrar ? 'block' : 'none';
            descripcion.required = mostrar;

            if (!mostrar) {
                descripcion.value = '';
            }
        }

        motivo.addEventListener('change', actualizarDescripcion);
        actualizarDescripcion();
    });
</script>
@endsection
