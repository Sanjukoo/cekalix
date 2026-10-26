<?php

namespace Database\Seeders;

use App\Models\Importacion;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImportacionSeeder extends Seeder
{
    public function run(): void
    {
        // Una importación por cada etapa del flujo: cerrada, lista para cerrar y recién llegada.
        // Cada SKU: [código, cajas facturadas, cajas contadas (null = sin contar), mermas [cantidad, motivo, descripción]]
        $embarques = [
            [
                'proveedor' => 'Proveedor Internacional A.',
                'factura' => 'JH-2026-1048',
                'contenedor' => 'TGHU4729136',
                'llegada' => '2026-10-14',
                'estado' => 'Procesada',
                'detalles' => [
                    ['COR-TE45-400', 120, 118, [[3, 'Cajas aplastadas', null]]],
                    ['BIS-CL35-110', 80, 84, []],
                    ['PIS-GAS100-260', 60, 60, [[2, 'Cajas mojadas', null]]],
                ],
            ],
            [
                'proveedor' => 'Global Trade Corp.',
                'factura' => 'GT-2026-1053',
                'contenedor' => 'MSKU6842057',
                'llegada' => '2026-10-15',
                'estado' => 'Procesada',
                'detalles' => [
                    ['COR-TE45-450', 100, 100, []],
                    ['BIS-CL35-165', 75, 73, [[1, 'Cajas rotas', null]]],
                    ['CER-MUE22-138', 90, 93, []],
                ],
            ],
            [
                'proveedor' => 'Importadora del Pacífico',
                'factura' => 'PI-2026-0876',
                'contenedor' => 'CMAU3158274',
                'llegada' => '2026-10-16',
                'estado' => 'En recepción',
                'detalles' => [
                    ['COR-TE45-500', 50, 48, [[2, 'OTRO', 'Rieles doblados por mala estiba en el contenedor']]],
                    ['PIS-GAS120-270', 40, 40, []],
                ],
            ],
            [
                'proveedor' => 'Proveedor Internacional A.',
                'factura' => 'JH-2026-1102',
                'contenedor' => 'TGHU5093318',
                'llegada' => '2026-10-22',
                'estado' => 'En recepción',
                'detalles' => [
                    ['BIS-CL35-110', 60, null, []],
                    ['BIS-REC-95', 50, null, []],
                    // SKU aún no registrado como producto: aparece en "Nuevo producto".
                    ['COR-OC-350', 30, null, []],
                ],
            ],
        ];

        DB::transaction(function () use ($embarques) {
            $encargado = User::where('email', 'inventario@cekalix.com')->firstOrFail();

            foreach ($embarques as $embarque) {
                $proveedor = Proveedor::where('razon_social', $embarque['proveedor'])->firstOrFail();
                $registro = Carbon::parse($embarque['llegada'].' 09:30:00', 'America/Lima')->utc();
                $importacion = Importacion::firstOrCreate([
                    'proveedor_id' => $proveedor->id,
                    'numero_factura' => $embarque['factura'],
                ], [
                    'numero_contenedor' => $embarque['contenedor'],
                    'fecha_llegada' => $embarque['llegada'],
                    'estado' => 'En recepción',
                ]);

                // Si la importación ya existía, no se toca: así no se suma stock dos veces.
                if (!$importacion->wasRecentlyCreated) {
                    continue;
                }

                $importacion->forceFill(['created_at' => $registro, 'updated_at' => $registro])->save();

                foreach ($embarque['detalles'] as [$codigo, $facturadas, $contadas, $mermas]) {
                    $detalle = $importacion->detalles()->create([
                        'producto_id' => Producto::where('codigo', $codigo)->value('id'),
                        'producto_codigo' => $codigo,
                        'cajas_facturadas' => $facturadas,
                    ]);

                    if ($contadas === null) {
                        continue;
                    }

                    $conteo = $detalle->conteoFisico()->create([
                        'cajas_contadas' => $contadas,
                        'diferencia' => $contadas - $facturadas,
                        'registrado_por' => $encargado->id,
                    ]);
                    $conteo->forceFill(['created_at' => $registro->copy()->addHours(2), 'updated_at' => $registro->copy()->addHours(2)])->save();

                    foreach ($mermas as [$cantidad, $motivo, $descripcion]) {
                        $detalle->mermas()->create([
                            'cantidad' => $cantidad,
                            'motivo' => $motivo,
                            'descripcion' => $descripcion,
                            'fecha_registro' => $embarque['llegada'],
                        ]);
                    }
                }

                if ($embarque['estado'] === 'Procesada') {
                    $this->cerrar($importacion);
                }
            }
        });
    }

    // Mismo cálculo que el cierre de recepción: (contadas - dañadas) × unidades por caja.
    private function cerrar(Importacion $importacion): void
    {
        $detalles = $importacion->detalles()
            ->with('conteoFisico', 'producto')
            ->withSum('mermas', 'cantidad')
            ->get();

        foreach ($detalles as $detalle) {
            $cajasBuenas = $detalle->conteoFisico->cajas_contadas - (int) $detalle->mermas_sum_cantidad;
            MovimientoInventario::registrar(
                $detalle->producto,
                max(0, $cajasBuenas) * $detalle->producto->unidades_por_caja,
                'cierre_importacion',
                "Recepción de importación #{$importacion->id}",
                $importacion
            );
        }

        $importacion->update(['estado' => 'Procesada']);
    }
}
