{{-- Pestañas de un mismo grupo (socios o recepción). Solo muestra las que el rol puede usar. --}}
@php
    $rol = auth()->user()->role;

    $grupos = [
        'socios' => [
            'proveedores' => ['Proveedores', route('proveedores.index'), 'bi-truck', $rol === 'admin'],
            'clientes' => ['Clientes', route('clientes.index'), 'bi-people', in_array($rol, ['admin', 'ventas'], true)],
        ],
        'recepcion' => [
            'importaciones' => ['Importaciones', route('importaciones.index'), 'bi-box-seam', in_array($rol, ['admin', 'inventario'], true)],
            'mermas' => ['Mermas', route('mermas.index'), 'bi-exclamation-octagon', in_array($rol, ['admin', 'inventario'], true)],
        ],
    ];
@endphp

<nav class="app-tabs" aria-label="Secciones relacionadas">
    @foreach($grupos[$hubActivo] as $clave => [$etiqueta, $url, $icono, $visible])
        @continue(! $visible)

        <a href="{{ $url }}" class="app-tab {{ $clave === $activa ? 'active' : '' }}">
            <i class="bi {{ $icono }}"></i>
            {{ $etiqueta }}
        </a>
    @endforeach
</nav>
