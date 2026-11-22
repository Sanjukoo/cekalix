@extends('layouts.app')

@section('title', 'Importaciones')

@section('content')
@include('layouts._hub', ['hubActivo' => 'recepcion', 'activa' => 'importaciones'])

<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="page-title">Importaciones Registradas</h2>
    </div>

    <div class="col-md-4 text-end">
        @if(auth()->user()->role === 'admin')
            <a href="{{ route('importaciones.create') }}" class="btn btn-rojo">
                Nueva Importación
            </a>
        @endif
    </div>
</div>

{{-- FILTROS --}}
<div class="card card-custom mb-4">
    <div class="card-body">
        <form action="{{ route('importaciones.index') }}" method="GET">
            {{-- Conserva el estado elegido al buscar --}}
            @if($estadoActual)
                <input type="hidden" name="estado" value="{{ $estadoActual }}">
            @endif

            <div class="row g-2">
                <div class="col-md-6">
                    <input type="text"
                           name="buscar"
                           class="form-control"
                           placeholder="Buscar por factura, SKU o producto..."
                           aria-label="Buscar importaciones"
                           value="{{ $buscar }}">
                </div>

                <div class="col-md-3">
                    <select name="proveedor" class="form-select" aria-label="Filtrar por proveedor"
                            onchange="this.form.submit()">
                        <option value="">Todos los proveedores</option>
                        @foreach($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}" @selected($proveedorActual?->id === $proveedor->id)>
                                {{ $proveedor->razon_social }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-rojo">Buscar</button>
                    <a href="{{ route('importaciones.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </div>
        </form>

        @php
            // Los botones de estado conservan la búsqueda y el proveedor.
            $filtroUrl = fn ($estado = null) => route('importaciones.index', array_filter([
                'buscar' => $buscar !== '' ? $buscar : null,
                'proveedor' => $proveedorActual?->id,
                'estado' => $estado,
            ]));
        @endphp

        <nav class="filtro-categorias mt-3" aria-label="Filtrar por estado">
            <span class="filtro-categorias-titulo">Estado:</span>

            <a href="{{ $filtroUrl() }}"
               class="filtro-chip {{ $estadoActual ? '' : 'activo' }}"
               @if(!$estadoActual) aria-current="true" @endif>
                Todas
                <span class="filtro-chip-total">{{ $conteoPorEstado->sum() }}</span>
            </a>

            @foreach($estados as $estado)
                @php $total = (int) ($conteoPorEstado[$estado] ?? 0); @endphp

                <a href="{{ $filtroUrl($estado) }}"
                   class="filtro-chip {{ $estadoActual === $estado ? 'activo' : '' }} {{ $total === 0 ? 'vacio' : '' }}"
                   @if($estadoActual === $estado) aria-current="true" @endif>
                    {{ $estado }}
                    <span class="filtro-chip-total">{{ $total }}</span>
                </a>
            @endforeach
        </nav>
    </div>
</div>

{{-- LISTADO: UNA FILA POR IMPORTACIÓN --}}
<div class="card card-custom">
    <div class="card-body">
        @if($importaciones->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle tabla-importaciones">
                    <thead>
                        <tr>
                            <th scope="col" class="col-desplegar"><span class="visually-hidden">Detalle</span></th>
                            <th scope="col">ID</th>
                            <th scope="col">Proveedor</th>
                            <th scope="col">Factura</th>
                            <th scope="col">Llegada</th>
                            <th scope="col">Contenido</th>
                            <th scope="col">Avance</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($importaciones as $importacion)
                            @php
                                $detalles = $importacion->detalles;
                                $totalSku = $detalles->count();
                                $totalCajas = $detalles->sum('cajas_facturadas');
                                $enRecepcion = $importacion->estado === 'En recepción';

                                // Qué falta para cerrar la recepción.
                                $sinConteo = $detalles->filter(fn ($d) => !$d->conteoFisico)->count();
                                $sinRegistrar = $detalles->filter(fn ($d) => !$d->producto_id)->count();
                                $idDetalle = 'detalle-importacion-'.$importacion->id;
                            @endphp

                            <tr>
                                <td class="col-desplegar">
                                    <button type="button"
                                            class="btn-desplegar"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#{{ $idDetalle }}"
                                            aria-expanded="false"
                                            aria-controls="{{ $idDetalle }}"
                                            aria-label="Ver los SKU de la importación #{{ $importacion->id }}">
                                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                    </button>
                                </td>

                                <td><strong>#{{ $importacion->id }}</strong></td>

                                <td>{{ $importacion->proveedor->razon_social }}</td>

                                <td>{{ $importacion->numero_factura }}</td>

                                <td>{{ $importacion->fecha_llegada->format('d/m/Y') }}</td>

                                <td class="text-nowrap">
                                    {{ $totalSku }} SKU
                                    <span class="text-muted">·</span>
                                    {{ $totalCajas }} {{ $totalCajas === 1 ? 'caja' : 'cajas' }}
                                </td>

                                <td>
                                    @if(!$enRecepcion)
                                        <span class="avance avance-ok">
                                            <i class="bi bi-check-circle" aria-hidden="true"></i> Stock ingresado
                                        </span>
                                    @else
                                        @if($sinConteo > 0)
                                            <span class="avance avance-pendiente">
                                                <i class="bi bi-clipboard" aria-hidden="true"></i>
                                                {{ $sinConteo === $totalSku ? 'Falta el conteo físico' : "Faltan contar {$sinConteo} SKU" }}
                                            </span>
                                        @endif

                                        @if($sinRegistrar > 0)
                                            <span class="avance avance-alerta">
                                                <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                                                {{ $sinRegistrar }} SKU sin registrar
                                            </span>
                                        @endif

                                        @if($sinConteo === 0 && $sinRegistrar === 0)
                                            <span class="avance avance-listo">
                                                <i class="bi bi-flag" aria-hidden="true"></i> Lista para cerrar
                                            </span>
                                        @endif
                                    @endif
                                </td>

                                <td>
                                    <span class="badge badge-estado {{ $enRecepcion ? 'en-recepcion' : 'procesada' }}">
                                        {{ $importacion->estado }}
                                    </span>
                                </td>

                                <td>
                                    <a href="{{ route('importaciones.show', $importacion) }}"
                                       class="btn btn-sm btn-outline-dark text-nowrap">
                                        {{ $enRecepcion ? 'Ver conteo' : 'Ver detalle' }}
                                    </a>
                                </td>
                            </tr>

                            {{-- Detalle desplegable con los SKU de la importación --}}
                            <tr class="collapse fila-detalle" id="{{ $idDetalle }}">
                                <td colspan="9">
                                    <table class="table table-sm mb-0 tabla-detalle-sku">
                                        <thead>
                                            <tr>
                                                <th scope="col">SKU</th>
                                                <th scope="col">Producto</th>
                                                <th scope="col" class="text-end">Facturadas</th>
                                                <th scope="col" class="text-end">Contadas</th>
                                                <th scope="col" class="text-end">Dañadas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($detalles as $detalle)
                                                @php
                                                    $contadas = $detalle->conteoFisico?->cajas_contadas;
                                                    $diferencia = $contadas !== null ? $contadas - $detalle->cajas_facturadas : null;
                                                    $danadas = (int) $detalle->mermas_sum_cantidad;
                                                @endphp
                                                <tr>
                                                    <td><span class="badge-sku">{{ $detalle->producto_codigo }}</span></td>
                                                    <td>
                                                        @if($detalle->producto)
                                                            {{ $detalle->producto->nombre }}
                                                        @else
                                                            <span class="text-warning">Pendiente de registro en Inventario</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end">{{ $detalle->cajas_facturadas }}</td>
                                                    <td class="text-end">
                                                        @if($contadas === null)
                                                            <span class="text-muted">Sin contar</span>
                                                        @else
                                                            {{ $contadas }}
                                                            @if($diferencia !== 0)
                                                                <small class="{{ $diferencia < 0 ? 'text-danger' : 'text-success' }}">
                                                                    ({{ $diferencia > 0 ? '+' : '' }}{{ $diferencia }})
                                                                </small>
                                                            @endif
                                                        @endif
                                                    </td>
                                                    <td class="text-end">{{ $danadas ?: '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $importaciones->links() }}
            </div>
        @else
            <p class="text-center text-muted py-4">
                @if($buscar !== '' || $proveedorActual || $estadoActual)
                    No hay importaciones que coincidan con los filtros.
                    <a href="{{ route('importaciones.index') }}">Ver todas</a>
                @else
                    No hay importaciones registradas.
                @endif
            </p>
        @endif
    </div>
</div>
@endsection
