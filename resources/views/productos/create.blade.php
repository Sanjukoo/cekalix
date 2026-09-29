@extends('layouts.app')

@section('title', 'Crear Producto')

@section('content')

<div class="row mb-4">
    <div class="col-12">
        <h2 style="color: #000000;">Nuevo Producto</h2>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header">
        Datos del Producto
    </div>

    <div class="card-body">

        <form action="{{ route('productos.store') }}" method="POST" novalidate>

            @csrf

            {{-- DATOS PRINCIPALES --}}
            <div class="row mb-3">

                <div class="col-md-4">
                    <label for="codigo" class="form-label">
                        Código <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        id="codigo"
                        name="codigo"
                        class="form-control @error('codigo') is-invalid @enderror"
                        value="{{ old('codigo') }}"
                        required
                    >

                    @error('codigo')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="nombre" class="form-label">
                        Nombre del Producto <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        class="form-control @error('nombre') is-invalid @enderror"
                        value="{{ old('nombre') }}"
                        required
                    >

                    @error('nombre')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="proveedor" class="form-label">
                        Proveedor <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        id="proveedor"
                        name="proveedor"
                        class="form-control @error('proveedor') is-invalid @enderror"
                        value="{{ old('proveedor') }}"
                        required
                    >

                    @error('proveedor')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

            </div>


            {{-- CATEGORIA, STOCK Y UNIDADES --}}
            <div class="row mb-3">

                <div class="col-md-4">

                    <label for="categoria_id" class="form-label">
                        Categoría <span class="text-danger">*</span>
                    </label>

                    <select
                        id="categoria_id"
                        name="categoria_id"
                        class="form-select @error('categoria_id') is-invalid @enderror"
                        required
                        onchange="actualizarAtributos()"
                    >

                        <option value="">
                            -- Selecciona una categoría --
                        </option>

                        @foreach($categorias as $categoria)

                            <option
                                value="{{ $categoria->id }}"
                                data-slug="{{ $categoria->slug }}"
                                @selected(old('categoria_id') == $categoria->id)
                            >
                                {{ $categoria->nombre }}
                            </option>

                        @endforeach

                        {{-- NUEVA CATEGORÍA --}}
                        <option value="nueva">
                            + Nueva categoría
                        </option>

                    </select>

                    @error('categoria_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div class="col-md-4">

                    <label for="stock" class="form-label">
                        Stock <span class="text-danger">*</span>
                    </label>

                    <input
                        type="number"
                        id="stock"
                        name="stock"
                        class="form-control @error('stock') is-invalid @enderror"
                        value="{{ old('stock', 0) }}"
                        min="0"
                        required
                    >

                    @error('stock')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div class="col-md-4">

                    <label for="unidades_por_caja" class="form-label">
                        Unidades por Caja <span class="text-danger">*</span>
                    </label>

                    <input
                        type="number"
                        id="unidades_por_caja"
                        name="unidades_por_caja"
                        class="form-control @error('unidades_por_caja') is-invalid @enderror"
                        value="{{ old('unidades_por_caja', 1) }}"
                        min="1"
                        required
                    >

                    @error('unidades_por_caja')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>


            {{-- DESCRIPCION --}}
            <div class="mb-3">

                <label for="descripcion" class="form-label">
                    Descripción
                </label>

                <textarea
                    id="descripcion"
                    name="descripcion"
                    class="form-control"
                    rows="3"
                >{{ old('descripcion') }}</textarea>

            </div>


            <hr>


            {{-- ATRIBUTOS DINAMICOS --}}
            <div id="atributos-container"></div>


            {{-- BOTONES --}}
            <div class="mt-4">

                <button
                    type="submit"
                    class="btn btn-rojo"
                >
                    Registrar Producto
                </button>

                <a
                    href="{{ route('productos.index') }}"
                    class="btn btn-secondary"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </div>
</div>


{{-- JAVASCRIPT --}}
<script>

    const atributosPorCategoria = @json(
        $categorias->mapWithKeys(function ($categoria) {
            return [
                $categoria->slug => $categoria->atributos->map(function ($atributo) {
                    return [
                        'nombre' => $atributo->nombre,
                        'campo' => \Illuminate\Support\Str::slug($atributo->nombre)
                    ];
                })
            ];
        })
    );

    function actualizarAtributos() {

        const selectElement =
            document.getElementById('categoria_id');

        const selectedOption =
            selectElement.options[selectElement.selectedIndex];


        // Si selecciona "Nueva categoría"
        if (selectedOption.value === 'nueva') {

            window.location.href =
                "{{ route('categorias.create') }}";

            return;
        }


        const categoriaSlug =
            selectedOption.dataset.slug;

        const container =
            document.getElementById('atributos-container');


        // Limpiar atributos anteriores
        container.innerHTML = '';


        // Verificar si existe una categoría seleccionada
        if (
            categoriaSlug &&
            atributosPorCategoria[categoriaSlug]
        ) {

            const atributos =
                atributosPorCategoria[categoriaSlug];


            let html = `
                <h6 class="text-danger">
                    Atributos de ${selectedOption.text}
                </h6>

                <div class="row">
            `;

            atributos.forEach(atributo => {

                html += `
                    <div class="col-md-6 mb-3">

                        <label
                            for="atributo_${atributo.campo}"
                            class="form-label"
                        >
                            ${atributo.nombre}
                        </label>

                        <input
                            type="text"
                            id="atributo_${atributo.campo}"
                            name="atributo_${atributo.campo}"
                            class="form-control"
                            value=""
                        >

                    </div>
                `;

            });


            html += `
                </div>
            `;


            container.innerHTML = html;

        }

    }

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            actualizarAtributos();
        }
    );

</script>

@endsection