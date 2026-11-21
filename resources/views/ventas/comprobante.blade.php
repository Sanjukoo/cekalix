<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante {{ $venta->numero }}</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #222;
        }

        h1 {
            font-size: 18px;
            margin: 0;
            color: #8b0000;
        }

        h2 {
            font-size: 13px;
            margin: 14px 0 6px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .encabezado td {
            vertical-align: top;
        }

        .derecha {
            text-align: right;
        }

        .centro {
            text-align: center;
        }

        .muted {
            color: #666;
        }

        .tabla-items th {
            background: #222;
            color: #fff;
            padding: 6px 4px;
            font-size: 9px;
        }

        .tabla-items td {
            border-bottom: 1px solid #ddd;
            padding: 6px 4px;
            font-size: 9px;
        }

        .totales td {
            padding: 5px 6px;
        }

        .total {
            font-size: 14px;
            font-weight: bold;
        }

        .anulada {
            color: #b00000;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
        }

        .pie {
            margin-top: 24px;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
    </style>
</head>

<body>

    <table class="encabezado">
        <tr>
            <td>
                <h1>CEKALIX</h1>
                <div class="muted">Sistema de Gestión de Inventario</div>
            </td>

            <td class="derecha">
                <strong>COMPROBANTE DE VENTA</strong><br>
                N° {{ $venta->numero }}<br>
                Fecha: {{ $venta->fecha_venta->format('d/m/Y') }}
            </td>
        </tr>
    </table>

    @if($venta->estaAnulada())
        <div class="anulada">VENTA ANULADA</div>
    @endif

    <h2>Datos del cliente</h2>

    <table>
        <tr>
            <td>
                <strong>Cliente:</strong> {{ $venta->nombreCliente() }}
            </td>
        </tr>

        @if($venta->cliente?->numero_documento)
            <tr>
                <td>
                    <strong>{{ $venta->cliente->tipo_documento }}:</strong>
                    {{ $venta->cliente->numero_documento }}
                </td>
            </tr>
        @endif

        @if($venta->cliente?->direccion)
            <tr>
                <td>
                    <strong>Dirección:</strong>
                    {{ $venta->cliente->direccion }}
                </td>
            </tr>
        @endif

        @if($venta->cliente?->telefono)
            <tr>
                <td>
                    <strong>Teléfono:</strong>
                    {{ $venta->cliente->telefono }}
                </td>
            </tr>
        @endif
    </table>

    <h2>Detalle de productos</h2>

    <table class="tabla-items">
        <thead>
            <tr>
                <th class="centro">#</th>
                <th>Código</th>
                <th>Producto</th>
                <th class="centro">Vendido por</th>
                <th class="centro">Cantidad</th>
                <th class="centro">Total unid.</th>
                <th class="derecha">P. unitario</th>
                <th class="derecha">Subtotal</th>
            </tr>
        </thead>

        <tbody>
            @foreach($venta->detalles as $detalle)
                <tr>
                    <td class="centro">{{ $loop->iteration }}</td>

                    <td>{{ $detalle->producto?->codigo ?? 'N/A' }}</td>

                    <td>{{ $detalle->producto?->nombre ?? 'Producto no disponible' }}</td>

                    <td class="centro">{{ $detalle->descripcionModalidad() }}</td>

                    <td class="centro">{{ $detalle->descripcionCantidad() }}</td>

                    <td class="centro">{{ $detalle->cantidad }}</td>

                    <td class="derecha">
                        S/ {{ number_format($detalle->precio_unitario, 2) }}
                    </td>

                    <td class="derecha">
                        S/ {{ number_format($detalle->subtotal, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totales" style="margin-top: 12px;">
        <tr>
            <td class="derecha">
                Subtotal:
                S/ {{ number_format($venta->subtotal, 2) }}
            </td>
        </tr>

        <tr>
            <td class="derecha">
                IGV (18 %):
                S/ {{ number_format($venta->igv, 2) }}
            </td>
        </tr>

        <tr>
            <td class="derecha total">
                TOTAL:
                S/ {{ number_format($venta->total, 2) }}
            </td>
        </tr>
    </table>

    <div class="pie">
        Atendido por {{ $venta->usuario?->name ?? 'Usuario desconocido' }}.<br>
        Este documento es un comprobante interno de venta.
        No reemplaza a la boleta o factura electrónica.
    </div>

</body>
</html>
