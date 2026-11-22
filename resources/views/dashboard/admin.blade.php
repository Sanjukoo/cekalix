@extends('layouts.app')

@section('title', 'Mi panel')

@section('content')

<div class="mb-4">
    <h1 class="page-title">Hola, {{ Auth::user()->name }}</h1>
    <p class="text-muted mb-0">Así está hoy tu negocio.</p>
</div>

{{-- INDICADORES PRINCIPALES --}}
<div class="row g-3 mb-4">

    @include('dashboard._indicador', [
        'icono' => 'bi-cash-coin',
        'titulo' => 'Ventas de hoy',
        'valor' => 'S/ '.number_format($ventasHoy, 2),
        'enlace' => route('ventas.index'),
        'textoEnlace' => 'Ver ventas',
    ])

    @include('dashboard._indicador', [
        'icono' => 'bi-graph-up-arrow',
        'titulo' => 'Ventas del mes',
        'valor' => 'S/ '.number_format($ventasMes, 2),
    ])

    @include('dashboard._indicador', [
        'icono' => 'bi-exclamation-triangle',
        'titulo' => 'Productos con stock bajo',
        'valor' => $stockBajo,
        'alerta' => $stockBajo > 0,
        'enlace' => route('productos.index'),
        'textoEnlace' => 'Revisar stock',
    ])

    @include('dashboard._indicador', [
        'icono' => 'bi-truck',
        'titulo' => 'Importaciones en recepción',
        'valor' => $enRecepcion,
        'enlace' => route('importaciones.index'),
        'textoEnlace' => 'Ver recepción',
    ])

</div>

{{-- GRÁFICO DE VENTAS --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

            <div>
                <h2 class="h5 fw-bold mb-1">Avance de ventas</h2>
                <p class="text-muted small mb-0">
                    Consulta el comportamiento de tus ventas según el periodo.
                </p>
            </div>

            <div class="btn-group" role="group" aria-label="Periodo de ventas">

                <button type="button"
                        class="btn btn-outline-primary active"
                        data-periodo="semanal">
                    Venta semanal
                </button>

                <button type="button"
                        class="btn btn-outline-primary"
                        data-periodo="mensual">
                    Venta mensual
                </button>

                <button type="button"
                        class="btn btn-outline-primary"
                        data-periodo="anual">
                    Venta anual
                </button>

            </div>

        </div>

        <div style="height: 320px; position: relative;">
            <canvas id="graficoVentas"></canvas>
        </div>

    </div>
</div>

{{-- GRÁFICOS DE MERMAS, STOCK Y PRODUCTOS MÁS VENDIDOS --}}
<div class="row g-3 mb-4">

    <div class="col-xl-4 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">

                <h2 class="h5 fw-bold mb-1">Mermas registradas</h2>

                <p class="text-muted small mb-3">
                    Cantidad de cajas dañadas durante el año.
                </p>

                <div style="height: 300px; position: relative;">
                    <canvas id="graficoMermas"></canvas>
                </div>

            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">

                <h2 class="h5 fw-bold mb-1">Stock de productos</h2>

                <p class="text-muted small mb-3">
                    Productos con menor disponibilidad (en unidades).
                </p>

                <div style="height: 300px; position: relative;">
                    <canvas id="graficoStock"></canvas>
                </div>

            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">

                <h2 class="h5 fw-bold mb-1">Productos más vendidos</h2>

                <p class="text-muted small mb-3">
                    Top 5 por unidades vendidas en todo el sistema.
                </p>

                @if($topVendidos->isEmpty())
                    <div class="top-vendidos-vacio">
                        <i class="bi bi-pie-chart"></i>
                        <span>Aún no hay ventas registradas.</span>
                    </div>
                @else
                    <div class="top-vendidos-grafico">
                        <canvas id="graficoTopVendidos"
                                role="img"
                                aria-label="Gráfico circular de los 5 productos más vendidos por unidades"></canvas>

                        <div class="top-vendidos-centro" aria-hidden="true">
                            <strong>{{ number_format($topVendidos->sum('unidades')) }}</strong>
                            <span>unidades</span>
                        </div>
                    </div>

                    {{-- Leyenda con cifras: identifica cada porción sin depender solo del color --}}
                    <ol class="top-vendidos-lista">
                        @foreach($topVendidos as $indice => $item)
                            <li data-indice="{{ $indice }}">
                                <span class="top-vendidos-color" style="--color-serie: var(--serie-{{ $indice + 1 }});"></span>
                                <span class="top-vendidos-producto" title="{{ $item['codigo'] }} · {{ $item['nombre'] }}">
                                    @if($item['codigo'])
                                        <small class="top-vendidos-codigo">{{ $item['codigo'] }}</small>
                                    @endif
                                    <span class="top-vendidos-nombre">{{ $item['nombre'] }}</span>
                                </span>
                                <span class="top-vendidos-cifra">
                                    {{ number_format($item['unidades']) }} u.
                                    <small>{{ $item['porcentaje'] }} %</small>
                                </span>
                            </li>
                        @endforeach
                    </ol>

                    <p class="top-vendidos-nota">
                        Porcentaje sobre {{ number_format($unidadesVendidasTotal) }} unidades vendidas en total.
                    </p>
                @endif

            </div>
        </div>
    </div>

</div>

{{-- ACCESOS RÁPIDOS --}}
<h2 class="h6 text-uppercase text-muted fw-bold mb-3">
    Accesos rápidos
</h2>

<div class="row g-3">

    <div class="col-md-4">
        <a href="{{ route('ventas.create') }}" class="acceso">
            <span class="acceso-icono">
                <i class="bi bi-plus-lg"></i>
            </span>
            <span>
                <strong>Registrar venta</strong>
                <small>Nueva salida de productos</small>
            </span>
        </a>
    </div>

    <div class="col-md-4">
        <a href="{{ route('productos.index') }}" class="acceso">
            <span class="acceso-icono">
                <i class="bi bi-box-seam"></i>
            </span>
            <span>
                <strong>Catálogo y stock</strong>
                <small>Productos, precios e historial</small>
            </span>
        </a>
    </div>

    <div class="col-md-4">
        <a href="{{ route('proveedores.index') }}" class="acceso">
            <span class="acceso-icono">
                <i class="bi bi-people"></i>
            </span>
            <span>
                <strong>Socios</strong>
                <small>Proveedores y clientes</small>
            </span>
        </a>
    </div>

</div>

@endsection

{{-- ESTILOS DE LOS GRÁFICOS --}}
@section('styles')
<style>
    .grafico-periodo.activo {
        background-color: #0d6efd;
        color: white;
    }

    .btn-group .btn {
        white-space: nowrap;
    }

    /* Productos más vendidos: colores categóricos en orden fijo (1 = más vendido) */
    :root {
        --serie-1: #2a78d6;
        --serie-2: #eb6834;
        --serie-3: #1baf7a;
        --serie-4: #eda100;
        --serie-5: #e87ba4;
    }

    .top-vendidos-grafico {
        position: relative;
        height: 190px;
        margin-bottom: 1rem;
    }

    .top-vendidos-centro {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        line-height: 1.1;
    }

    .top-vendidos-centro strong {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--ck-texto);
    }

    .top-vendidos-centro span {
        font-size: 0.75rem;
        color: var(--ck-texto-suave);
    }

    .top-vendidos-lista {
        list-style: none;
        margin: 0;
        padding: 0;
        font-size: 0.85rem;
    }

    .top-vendidos-lista li {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        padding: 0.3rem 0.4rem;
        border-radius: 0.4rem;
        transition: background-color 0.15s;
    }

    .top-vendidos-lista li.resaltado {
        background-color: var(--ck-fondo);
    }

    .top-vendidos-color {
        flex: 0 0 auto;
        width: 0.7rem;
        height: 0.7rem;
        margin-top: 0.3rem;
        border-radius: 0.2rem;
        background-color: var(--color-serie);
    }

    .top-vendidos-producto {
        flex: 1 1 auto;
        min-width: 0;
        display: flex;
        flex-direction: column;
        line-height: 1.25;
    }

    .top-vendidos-codigo {
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        color: var(--ck-texto-suave);
    }

    /* Nombre completo en hasta dos líneas: el final (la medida) no se pierde */
    .top-vendidos-nombre {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        overflow-wrap: anywhere;
        color: var(--ck-texto);
    }

    .top-vendidos-cifra {
        margin-top: 0.15rem;
    }

    .top-vendidos-cifra {
        flex: 0 0 auto;
        font-weight: 600;
        color: var(--ck-texto);
        font-variant-numeric: tabular-nums;
    }

    .top-vendidos-cifra small {
        margin-left: 0.25rem;
        font-weight: 500;
        color: var(--ck-texto-suave);
    }

    .top-vendidos-nota {
        margin: 0.6rem 0 0;
        font-size: 0.75rem;
        color: var(--ck-texto-suave);
    }

    .top-vendidos-vacio {
        height: 300px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        color: var(--ck-texto-suave);
    }

    .top-vendidos-vacio i {
        font-size: 2rem;
    }

    @media (max-width: 576px) {
        .btn-group {
            display: flex;
            width: 100%;
        }

        .btn-group .btn {
            flex: 1;
            font-size: 0.75rem;
            padding: 0.45rem 0.3rem;
        }
    }
</style>
@endsection

{{-- BIBLIOTECA Y FUNCIONAMIENTO DE LOS GRÁFICOS --}}
@section('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // Datos enviados desde Laravel.
    const datosVentas = {
        semanal: {
            labels: @json($ventasSemanalLabels),
            datos: @json($ventasSemanalDatos)
        },
        mensual: {
            labels: @json($ventasMensualLabels),
            datos: @json($ventasMensualDatos)
        },
        anual: {
            labels: @json($ventasAnualLabels),
            datos: @json($ventasAnualDatos)
        }
    };

    // Los ejes muestran meses abreviados; el tooltip muestra el nombre completo.
    const mesesCortos = @json($ventasAnualLabels);
    const mesesCompletos = @json($mesesCompletos);

    function mesCompleto(etiqueta) {
        const indice = mesesCortos.indexOf(etiqueta);
        return indice === -1 ? etiqueta : mesesCompletos[indice];
    }

    // Configuración del gráfico de ventas.
    const contextoVentas = document.getElementById('graficoVentas');

    const graficoVentas = new Chart(contextoVentas, {
        type: 'bar',

        data: {
            labels: datosVentas.semanal.labels,

            datasets: [{
                label: 'Total vendido',
                data: datosVentas.semanal.datos,
                backgroundColor: 'rgba(54, 162, 235, 0.65)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1,
                borderRadius: 5
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: false
                },

                tooltip: {
                    callbacks: {
                        title: items => mesCompleto(items[0].label),
                        label: function(context) {
                            return 'S/ ' + Number(context.raw).toFixed(2);
                        }
                    }
                }
            },

            scales: {
                x: {
                    ticks: { maxRotation: 0, autoSkip: true }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'S/ ' + value;
                        }
                    }
                }
            }
        }
    });

    // Botones para cambiar el periodo de ventas.
    const botonesPeriodo = document.querySelectorAll('[data-periodo]');

    botonesPeriodo.forEach(function(boton) {

        boton.addEventListener('click', function() {

            const periodo = this.dataset.periodo;
            const informacion = datosVentas[periodo];

            graficoVentas.data.labels = informacion.labels;
            graficoVentas.data.datasets[0].data = informacion.datos;

            graficoVentas.update();

            // Actualizar el botón seleccionado.
            botonesPeriodo.forEach(function(elemento) {
                elemento.classList.remove('active');
            });

            this.classList.add('active');

        });

    });

    // GRÁFICO DE MERMAS.
    new Chart(document.getElementById('graficoMermas'), {

        type: 'bar',

        data: {
            labels: @json($mermasLabels),

            datasets: [{
                label: 'Cajas dañadas',
                data: @json($mermasDatos),
                backgroundColor: 'rgba(255, 159, 64, 0.7)',
                borderColor: 'rgb(255, 159, 64)',
                borderWidth: 1,
                borderRadius: 5
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: false
                },

                tooltip: {
                    callbacks: {
                        title: items => mesCompleto(items[0].label),
                        label: contexto => ' ' + contexto.raw + (contexto.raw === 1 ? ' caja dañada' : ' cajas dañadas')
                    }
                }
            },

            scales: {
                x: {
                    ticks: { maxRotation: 0, autoSkip: false }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }

    });

    // GRÁFICO DE STOCK.
    const nombresStock = @json($stockNombres);

    new Chart(document.getElementById('graficoStock'), {

        type: 'bar',

        data: {
            labels: @json($stockLabels),

            datasets: [{
                label: 'Unidades disponibles',
                data: @json($stockDatos),
                backgroundColor: 'rgba(75, 192, 192, 0.7)',
                borderColor: 'rgb(75, 192, 192)',
                borderWidth: 1,
                borderRadius: 5
            }]
        },

        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: false
                },

                // El eje muestra el SKU; el tooltip, el nombre completo.
                tooltip: {
                    callbacks: {
                        title: items => {
                            const nombre = nombresStock[items[0].dataIndex];
                            return nombre ? [items[0].label, nombre] : items[0].label;
                        },
                        label: contexto => ' ' + Number(contexto.raw).toLocaleString('es-PE') + ' unidades disponibles'
                    }
                }
            },

            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }

    });

    // GRÁFICO CIRCULAR: TOP 5 PRODUCTOS MÁS VENDIDOS.
    const lienzoTop = document.getElementById('graficoTopVendidos');

    if (lienzoTop) {
        const topVendidos = @json($topVendidos);
        const estilos = getComputedStyle(document.documentElement);
        const colores = topVendidos.map((_, i) => estilos.getPropertyValue('--serie-' + (i + 1)).trim());
        const filasLeyenda = document.querySelectorAll('.top-vendidos-lista li');

        const graficoTop = new Chart(lienzoTop, {
            type: 'doughnut',

            data: {
                labels: topVendidos.map(item => item.nombre),

                datasets: [{
                    data: topVendidos.map(item => item.unidades),
                    backgroundColor: colores,
                    // Separación de 2px con el color de la tarjeta entre porciones.
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                layout: { padding: 6 },

                plugins: {
                    // La leyenda con cifras está en HTML, debajo del gráfico.
                    legend: { display: false },

                    tooltip: {
                        callbacks: {
                            title: items => {
                                const item = topVendidos[items[0].dataIndex];
                                return item.codigo ? item.codigo + ' · ' + item.nombre : item.nombre;
                            },
                            label: contexto => {
                                const item = topVendidos[contexto.dataIndex];
                                return ' ' + item.unidades.toLocaleString('es-PE') + ' unidades (' + item.porcentaje + ' % del total)';
                            }
                        }
                    }
                },

                // Resaltar la fila de la leyenda de la porción señalada.
                onHover: (evento, elementos) => {
                    const activo = elementos.length ? elementos[0].index : -1;
                    filasLeyenda.forEach((fila, i) => fila.classList.toggle('resaltado', i === activo));
                }
            }
        });

        // Y al revés: pasar sobre la leyenda resalta la porción.
        filasLeyenda.forEach(function (fila, i) {
            fila.addEventListener('mouseenter', function () {
                graficoTop.setActiveElements([{ datasetIndex: 0, index: i }]);
                graficoTop.tooltip.setActiveElements([{ datasetIndex: 0, index: i }], { x: 0, y: 0 });
                graficoTop.update();
                fila.classList.add('resaltado');
            });

            fila.addEventListener('mouseleave', function () {
                graficoTop.setActiveElements([]);
                graficoTop.tooltip.setActiveElements([], { x: 0, y: 0 });
                graficoTop.update();
                fila.classList.remove('resaltado');
            });
        });
    }

});
</script>

@endsection
