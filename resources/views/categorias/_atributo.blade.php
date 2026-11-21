{{-- Una fila de atributo en el formulario de categoría: nombre + opciones separadas por comas --}}
<div class="row g-2 mb-2 align-items-start atributo" data-resumen-fila>
    <div class="col-md-4">
        <input
            type="text"
            name="atributos[{{ $indice }}][nombre]"
            data-resumen="Atributo"
            class="form-control"
            maxlength="100"
            placeholder="Atributo (ej.: Material)"
            aria-label="Nombre del atributo"
            value="{{ $atributo['nombre'] ?? '' }}"
            required
        >
    </div>

    <div class="col-md-6">
        <input
            type="text"
            name="atributos[{{ $indice }}][opciones]"
            data-resumen="Opciones"
            class="form-control"
            maxlength="1000"
            placeholder="Opciones separadas por comas (ej.: Acero, Aluminio, Zamak)"
            aria-label="Opciones del atributo"
            value="{{ $atributo['opciones'] ?? '' }}"
        >
    </div>

    <div class="col-md-2">
        <button type="button" class="btn btn-outline-rojo w-100 eliminar-atributo">
            Eliminar
        </button>
    </div>
</div>
