@extends('layouts.app')

@section('title', 'Nueva Categoría')

@section('content')

<div class="row mb-4">
    <div class="col-12">
        <h2 class="page-title">Nueva Categoría</h2>
    </div>
</div>

@php
    // Tras un error de validación se recupera lo escrito; si no, una fila vacía.
    $atributosIniciales = old('atributos', [['nombre' => '', 'opciones' => '']]);
@endphp

<div class="card card-custom">
    <div class="card-header">
        Datos de la Categoría
    </div>

    <div class="card-body">

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('categorias.store') }}" method="POST"
              data-confirm="Revisa los datos de la categoría y sus atributos antes de registrarla."
              data-confirm-titulo="Confirmar registro de categoría"
              data-confirm-boton="Guardar categoría">

            @csrf

            <div class="mb-3">
                <label for="nombre" class="form-label">
                    Nombre de la categoría <span class="text-danger">*</span>
                </label>

                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    class="form-control @error('nombre') is-invalid @enderror"
                    value="{{ old('nombre') }}"
                    maxlength="50"
                    placeholder="Ejemplo: Tiradores"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">
                    Descripción
                </label>

                <textarea
                    id="descripcion"
                    name="descripcion"
                    class="form-control"
                    rows="3"
                    placeholder="Descripción de la categoría"
                >{{ old('descripcion') }}</textarea>
            </div>

            <hr>

            <h5 class="mb-1">
                Atributos de la categoría
            </h5>

            <p class="text-muted small mb-3">
                Escribe las opciones separadas por comas para que el atributo se elija
                de una lista desplegable en la ficha del producto (ej.: <em>Acero, Aluminio, Zamak</em>).
                Si lo dejas vacío, se escribirá libremente.
            </p>

            <div id="atributos" data-resumen-grupo="Atributos">
                @foreach($atributosIniciales as $i => $atributo)
                    @include('categorias._atributo', ['indice' => $i, 'atributo' => $atributo])
                @endforeach
            </div>

            <template id="plantilla-atributo">
                @include('categorias._atributo', ['indice' => '__INDICE__', 'atributo' => []])
            </template>

            <button
                type="button"
                class="btn btn-outline-secondary mb-4"
                id="agregar-atributo"
            >
                + Agregar atributo
            </button>

            <div>
                <button type="submit" class="btn btn-rojo">
                    Guardar Categoría
                </button>

                <a href="{{ route('productos.create') }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>

        </form>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const contenedor = document.getElementById('atributos');
    const plantilla = document.getElementById('plantilla-atributo');
    let siguienteIndice = {{ count($atributosIniciales) }};

    function actualizarBotones() {
        const filas = contenedor.querySelectorAll('.atributo');
        filas.forEach(function (fila) {
            fila.querySelector('.eliminar-atributo').disabled = filas.length === 1;
        });
    }

    document.getElementById('agregar-atributo').addEventListener('click', function () {
        const html = plantilla.innerHTML.replaceAll('__INDICE__', siguienteIndice++);
        const envoltura = document.createElement('div');
        envoltura.innerHTML = html.trim();
        contenedor.appendChild(envoltura.firstElementChild);
        actualizarBotones();
    });

    contenedor.addEventListener('click', function (evento) {
        const boton = evento.target.closest('.eliminar-atributo');

        if (boton && !boton.disabled) {
            boton.closest('.atributo').remove();
            actualizarBotones();
        }
    });

    actualizarBotones();
});
</script>

@endsection
