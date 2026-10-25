<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        // [código, nombre, categoría, proveedor, unidades por caja, atributos]
        $productos = [
            ['COR-TE45-400', 'Corredera telescópica 45 mm x 400 mm', 'correderas', 'Proveedor Internacional A.', 15,
                ['longitud' => '400 mm', 'espesor' => '1.2 mm', 'ancho' => '45 mm', 'color' => 'Zincado']],
            ['COR-TE45-450', 'Corredera telescópica 45 mm x 450 mm', 'correderas', 'Global Trade Corp.', 15,
                ['longitud' => '450 mm', 'espesor' => '1.2 mm', 'ancho' => '45 mm', 'color' => 'Zincado']],
            ['COR-TE45-500', 'Corredera telescópica 45 mm x 500 mm', 'correderas', 'Importadora del Pacífico', 15,
                ['longitud' => '500 mm', 'espesor' => '1.2 mm', 'ancho' => '45 mm', 'color' => 'Negro']],
            ['BIS-CL35-110', 'Bisagra cazoleta 35 mm cierre lento 110°', 'bisagras', 'Proveedor Internacional A.', 100,
                ['tipo' => 'Cierre lento 110°', 'acabado' => 'Niquelado', 'peso' => '95 g']],
            ['BIS-CL35-165', 'Bisagra cazoleta 35 mm apertura 165°', 'bisagras', 'Global Trade Corp.', 100,
                ['tipo' => 'Apertura 165°', 'acabado' => 'Niquelado', 'peso' => '145 g']],
            ['BIS-REC-95', 'Bisagra recta 95° para puerta de mueble', 'bisagras', 'Proveedor Internacional A.', 100,
                ['tipo' => 'Recta 95°', 'acabado' => 'Niquelado', 'peso' => '80 g']],
            ['PIS-GAS100-260', 'Pistón a gas 100 N de 260 mm', 'pistones', 'Proveedor Internacional A.', 50,
                ['fuerza' => '100 N', 'longitud' => '260 mm', 'acabado' => 'Gris satinado']],
            ['PIS-GAS120-270', 'Pistón a gas 120 N de 270 mm', 'pistones', 'Importadora del Pacífico', 50,
                ['fuerza' => '120 N', 'longitud' => '270 mm', 'acabado' => 'Gris satinado']],
            ['CER-MUE22-138', 'Cerradura para mueble de cilindro 22 mm', 'cerraduras', 'Global Trade Corp.', 120,
                ['material' => 'Aleación de zinc', 'tamano' => '22 mm']],
            ['CER-COR-60', 'Cerradura para puerta corrediza de vidrio', 'cerraduras', 'Global Trade Corp.', 60,
                ['material' => 'Acero inoxidable', 'tamano' => '60 mm']],
        ];

        // El stock empieza en 0: solo lo suma el cierre de una importación.
        foreach ($productos as [$codigo, $nombre, $categoriaSlug, $proveedor, $unidades, $atributos]) {
            $producto = Producto::firstOrCreate(['codigo' => $codigo], [
                'nombre' => $nombre,
                'categoria_id' => Categoria::where('slug', $categoriaSlug)->firstOrFail()->id,
                'proveedor_id' => Proveedor::where('razon_social', $proveedor)->firstOrFail()->id,
                'stock' => 0,
                'unidades_por_caja' => $unidades,
                'descripcion' => $nombre.'. Presentación de '.$unidades.' unidades por caja.',
            ]);

            foreach ($atributos as $clave => $valor) {
                $producto->atributos()->firstOrCreate(['clave' => $clave], ['valor' => $valor]);
            }
        }
    }
}
