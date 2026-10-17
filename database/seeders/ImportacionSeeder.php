<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Importacion;
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
        $this->call([CategoriaSeeder::class, ProveedorSeeder::class, UserSeeder::class]);

        $embarques = [
            [
                'proveedor' => 'Proveedor Internacional A.',
                'factura' => 'JH-2026-1048',
                'contenedor' => 'TGHU4729136',
                'llegada' => '2026-10-14',
                'productos' => [
                    ['COR-TE45-400', 'Corredera telescópica 45 mm x 400 mm', 'correderas', 15, 120, 118,
                        ['longitud' => '400 mm', 'espesor' => '1.2 mm', 'ancho' => '45 mm', 'color' => 'Zincado']],
                    ['BIS-CL35-110', 'Bisagra cazoleta 35 mm cierre lento 110°', 'bisagras', 100, 80, 84,
                        ['tipo' => 'Cierre lento 110°', 'acabado' => 'Niquelado', 'peso' => '95 g']],
                    ['PIS-GAS100-260', 'Pistón a gas 100 N de 260 mm', 'pistones', 50, 60, 60,
                        ['fuerza' => '100 N', 'longitud' => '260 mm', 'acabado' => 'Gris satinado']],
                ],
            ],
            [
                'proveedor' => 'Global Trade Corp.',
                'factura' => 'GT-2026-1053',
                'contenedor' => 'MSKU6842057',
                'llegada' => '2026-10-15',
                'productos' => [
                    ['COR-TE45-450', 'Corredera telescópica 45 mm x 450 mm', 'correderas', 15, 100, 100,
                        ['longitud' => '450 mm', 'espesor' => '1.2 mm', 'ancho' => '45 mm', 'color' => 'Zincado']],
                    ['BIS-CL35-165', 'Bisagra cazoleta 35 mm apertura 165°', 'bisagras', 100, 75, 73,
                        ['tipo' => 'Apertura 165°', 'acabado' => 'Niquelado', 'peso' => '145 g']],
                    ['CER-MUE22-138', 'Cerradura para mueble de cilindro 22 mm', 'cerraduras', 120, 90, 93,
                        ['material' => 'Aleación de zinc', 'tamano' => '22 mm']],
                ],
            ],
            [
                'proveedor' => 'Importadora del Pacífico',
                'factura' => 'PI-2026-0876',
                'contenedor' => 'CMAU3158274',
                'llegada' => '2026-10-16',
                'productos' => [
                    ['COR-TE45-500', 'Corredera telescópica 45 mm x 500 mm', 'correderas', 15, 50, null,
                        ['longitud' => '500 mm', 'espesor' => '1.2 mm', 'ancho' => '45 mm', 'color' => 'Negro']],
                    ['PIS-GAS120-270', 'Pistón a gas 120 N de 270 mm', 'pistones', 50, 40, null,
                        ['fuerza' => '120 N', 'longitud' => '270 mm', 'acabado' => 'Gris satinado']],
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
                if ($importacion->wasRecentlyCreated) {
                    $importacion->forceFill(['created_at' => $registro, 'updated_at' => $registro])->save();
                }

                foreach ($embarque['productos'] as [$codigo, $nombre, $categoriaSlug, $unidades, $facturadas, $contadas, $atributos]) {
                    $categoria = Categoria::where('slug', $categoriaSlug)->firstOrFail();
                    $producto = Producto::firstOrCreate(['codigo' => $codigo], [
                        'nombre' => $nombre,
                        'categoria_id' => $categoria->id,
                        'proveedor_id' => $proveedor->id,
                        'stock' => 0,
                        'unidades_por_caja' => $unidades,
                        'descripcion' => $nombre.'. Presentación de '.$unidades.' unidades por caja.',
                    ]);
                    foreach ($atributos as $clave => $valor) {
                        $producto->atributos()->firstOrCreate(['clave' => $clave], ['valor' => $valor]);
                    }

                    $detalle = $importacion->detalles()->firstOrCreate(['producto_id' => $producto->id], [
                        'cajas_facturadas' => $facturadas,
                    ]);
                    if ($contadas !== null) {
                        $conteo = $detalle->conteoFisico()->firstOrCreate([], [
                            'cajas_contadas' => $contadas,
                            'diferencia' => $contadas - $detalle->cajas_facturadas,
                            'registrado_por' => $encargado->id,
                        ]);
                        if ($conteo->wasRecentlyCreated) {
                            $conteo->forceFill(['created_at' => $registro->copy()->addHours(2), 'updated_at' => $registro->copy()->addHours(2)])->save();
                        }
                    }

                }

            }
        });
    }
}
