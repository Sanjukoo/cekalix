<div class="col-6 col-xl-3">
    <div class="card card-custom kpi {{ ($alerta ?? false) ? 'alerta' : '' }}">
        <div class="kpi-icono">
            <i class="bi {{ $icono }}"></i>
        </div>

        <div class="min-w-0">
            <div class="kpi-etiqueta">{{ $titulo }}</div>
            <div class="kpi-valor">{{ $valor }}</div>

            @isset($enlace)
                <a href="{{ $enlace }}" class="kpi-enlace">{{ $textoEnlace }} →</a>
            @endisset
        </div>
    </div>
</div>
