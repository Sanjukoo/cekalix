<div class="row mb-3">
    <div class="col-md-8">
        <label for="nombre" class="form-label">Nombre o razón social <span class="text-danger">*</span></label>
        <input type="text" id="nombre" name="nombre" maxlength="100"
               class="form-control @error('nombre') is-invalid @enderror"
               value="{{ old('nombre', $cliente->nombre ?? '') }}" required>
        @error('nombre')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="tipo_documento" class="form-label">Tipo de documento</label>
        <select id="tipo_documento" name="tipo_documento" class="form-select @error('tipo_documento') is-invalid @enderror">
            <option value="">-- Sin documento --</option>
            @foreach(['DNI', 'RUC', 'CE', 'Otro'] as $tipo)
                <option value="{{ $tipo }}" @selected(old('tipo_documento', $cliente->tipo_documento ?? '') === $tipo)>
                    {{ $tipo }}
                </option>
            @endforeach
        </select>
        @error('tipo_documento')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="numero_documento" class="form-label">Número de documento</label>
        <input type="text" id="numero_documento" name="numero_documento" maxlength="20"
               class="form-control @error('numero_documento') is-invalid @enderror"
               value="{{ old('numero_documento', $cliente->numero_documento ?? '') }}">
        @error('numero_documento')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="telefono" class="form-label">Teléfono</label>
        <input type="text" id="telefono" name="telefono" maxlength="20"
               class="form-control @error('telefono') is-invalid @enderror"
               value="{{ old('telefono', $cliente->telefono ?? '') }}">
        @error('telefono')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <label for="direccion" class="form-label">Dirección</label>
    <input type="text" id="direccion" name="direccion" maxlength="255"
           class="form-control @error('direccion') is-invalid @enderror"
           value="{{ old('direccion', $cliente->direccion ?? '') }}">
    @error('direccion')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
