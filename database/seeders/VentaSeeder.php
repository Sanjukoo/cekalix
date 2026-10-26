<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VentaSeeder extends Seeder
{
    public function run(): void
    {
        // Solo siembra si todavía no hay ventas: volver a ejecutarlo descontaría el stock otra vez.
        if (Venta::exists()) {
            return;
        }

        // [cliente (nombre, null = Cliente general), fecha, [[código SKU, unidades], ...]]
        $ventas = [
            [
                'Mueblería El Roble',
                '2026-10-20',
                [['COR-TE45-400', 30], ['BIS-CL35-110', 50]],
            ],
            [
                null,
                '2026-10-23',
                [['CER-MUE22-138', 10], ['PIS-GAS100-260', 4]],
            ],
        ];

        DB::transaction(function () use ($ventas) {
            $vendedor = User::where('email', 'ventas@cekalix.com')->firstOrFail();

            foreach ($ventas as [$nombreCliente, $fecha, $lineas]) {
                $venta = Venta::create([
                    'cliente_id' => $nombreCliente
                        ? Cliente::where('nombre', $nombreCliente)->value('id')
                        : null,
                    'fecha_venta' => $fecha,
                    'total' => 0,
                    'estado' => Venta::VIGENTE,
                    'registrado_por' => $vendedor->id,
                ]);

                $total = 0;

                // Mismo cálculo que el módulo de ventas: cantidad × precio del producto, y el stock sale por movimiento.
                foreach ($lineas as [$codigo, $cantidad]) {
                    $producto = Producto::where('codigo', $codigo)->lockForUpdate()->firstOrFail();
                    $precioUnitario = (float) $producto->precio_venta;
                    $subtotal = round($cantidad * $precioUnitario, 2);

                    VentaDetalle::create([
                        'venta_id' => $venta->id,
                        'producto_id' => $producto->id,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioUnitario,
                        'subtotal' => $subtotal,
                    ]);

                    MovimientoInventario::registrar(
                        $producto,
                        -$cantidad,
                        'venta',
                        "Venta {$venta->numero}",
                        $venta
                    );

                    $total += $subtotal;
                }

                $venta->update(['total' => $total]);
            }
        });
    }
}
