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

{{-- GRÁFICOS DE MERMAS Y STOCK --}}
<div class="row g-3 mb-4">

    <div class="col-lg-6">
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

    <div class="col-lg-6">
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
                        label: function(context) {
                            return 'S/ ' + Number(context.raw).toFixed(2);
                        }
                    }
                }
            },

            scales: {
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
                }
            },

            scales: {
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
                }
            },

            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }

    });

});
</script>

@endsection
