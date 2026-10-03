{{-- Campos compartidos por los formularios de crear y editar proveedor --}}
@php
    $proveedor = $proveedor ?? null;
    // Tras un error de validación se respeta lo que marcó el usuario
    $activo = $errors->any() ? old('activo') : ($proveedor->activo ?? true);
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="razon_social" class="form-label">Razón social <span class="text-danger">*</span></label>
        <input type="text" id="razon_social" name="razon_social" class="form-control @error('razon_social') is-invalid @enderror"
               value="{{ old('razon_social', $proveedor?->razon_social) }}" maxlength="150" required>
        @error('razon_social')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="pais_origen" class="form-label">País de origen <span class="text-danger">*</span></label>
        <input type="text" id="pais_origen" name="pais_origen" list="paises" class="form-control @error('pais_origen') is-invalid @enderror"
               value="{{ old('pais_origen', $proveedor?->pais_origen) }}" maxlength="100" required>
        <datalist id="paises">
            <option value="China">
            <option value="Taiwán">
            <option value="Turquía">
            <option value="Alemania">
            <option value="Italia">
            <option value="Estados Unidos">
            <option value="Brasil">
            <option value="Perú">
        </datalist>
        @error('pais_origen')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="correo_electronico" class="form-label">Correo electrónico <span class="text-danger">*</span></label>
        <input type="email" id="correo_electronico" name="correo_electronico" class="form-control @error('correo_electronico') is-invalid @enderror"
               value="{{ old('correo_electronico', $proveedor?->correo_electronico) }}" maxlength="150" required>
        @error('correo_electronico')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="identificador_wechat" class="form-label">ID de WeChat <span class="text-danger">*</span></label>
        <input type="text" id="identificador_wechat" name="identificador_wechat" class="form-control @error('identificador_wechat') is-invalid @enderror"
               value="{{ old('identificador_wechat', $proveedor?->identificador_wechat) }}" maxlength="100" required>
        @error('identificador_wechat')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <div class="form-check">
        <input type="checkbox" id="activo" name="activo" class="form-check-input" value="1" @checked($activo)>
        <label class="form-check-label" for="activo">
            Proveedor activo
        </label>
    </div>
</div>
