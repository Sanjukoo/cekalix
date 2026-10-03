@extends('layouts.app')

@section('title', 'Nueva Categoría')

@section('content')

<div class="row mb-4">
    <div class="col-12">
        <h2 class="page-title">Nueva Categoría</h2>
    </div>
</div>

<div class="card card-custom">

    <div class="card-header">
        Datos de la Categoría
    </div>

    <div class="card-body">

        <form action="{{ route('categorias.store') }}" method="POST">

            @csrf

            <div class="mb-3">

                <label for="nombre" class="form-label">
                    Nombre de la categoría
                </label>

                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    class="form-control"
                    value="{{ old('nombre') }}"
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


            <h5 class="mb-3">
                Atributos de la categoría
            </h5>


            <div id="atributos">

                <div class="row mb-2 atributo">

                    <div class="col-md-10">

                        <input
                            type="text"
                            name="atributos[]"
                            class="form-control"
                            placeholder="Ejemplo: Material"
                            required
                        >

                    </div>

                    <div class="col-md-2">

                        <button
                            type="button"
                            class="btn btn-outline-rojo"
                            onclick="eliminarAtributo(this)"
                        >
                            Eliminar
                        </button>

                    </div>

                </div>

            </div>


            <button
                type="button"
                class="btn btn-outline-secondary mb-4"
                onclick="agregarAtributo()"
            >
                + Agregar atributo
            </button>


            <div>

                <button
                    type="submit"
                    class="btn btn-rojo"
                >
                    Guardar Categoría
                </button>

                <a
                    href="{{ route('productos.create') }}"
                    class="btn btn-outline-secondary"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </div>

</div>


<script>

function agregarAtributo() {

    const container = document.getElementById('atributos');

    const div = document.createElement('div');

    div.classList.add('row', 'mb-2', 'atributo');

    div.innerHTML = `
        <div class="col-md-10">

            <input
                type="text"
                name="atributos[]"
                class="form-control"
                placeholder="Ejemplo: Color"
                required
            >

        </div>

        <div class="col-md-2">

            <button
                type="button"
                class="btn btn-outline-rojo"
                onclick="eliminarAtributo(this)"
            >
                Eliminar
            </button>

        </div>
    `;

    container.appendChild(div);
}


function eliminarAtributo(button) {

    const atributos =
        document.querySelectorAll('.atributo');

    if (atributos.length > 1) {

        button.closest('.atributo').remove();

    }

}

</script>

@endsection