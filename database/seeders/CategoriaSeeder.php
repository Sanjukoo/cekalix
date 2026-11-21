<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /*
     * Categorías de ejemplo con sus atributos y la lista de opciones de cada uno.
     * Las opciones se muestran como lista desplegable en la ficha del producto.
     * También las usa la migración que agregó las opciones, para completar las
     * categorías que ya existían.
     */
    public const CATEGORIAS = [
        [
            'nombre' => 'Correderas',
            'slug' => 'correderas',
            'descripcion' => 'Sistemas de correderas para muebles y puertas',
            'atributos' => [
                'Longitud' => ['300 mm', '350 mm', '400 mm', '450 mm', '500 mm', '550 mm'],
                'Espesor' => ['1.0 mm', '1.2 mm', '1.5 mm'],
                'Ancho' => ['35 mm', '45 mm', '53 mm'],
                'Color' => ['Zincado', 'Negro', 'Blanco'],
            ],
        ],
        [
            'nombre' => 'Bisagras',
            'slug' => 'bisagras',
            'descripcion' => 'Bisagras y herrajes para puertas y muebles',
            'atributos' => [
                'Tipo' => ['Recta 95°', 'Cierre lento 110°', 'Apertura 165°', 'Semicodo', 'Codo'],
                'Acabado' => ['Niquelado', 'Negro mate', 'Acero inoxidable'],
                'Peso' => ['80 g', '95 g', '120 g', '145 g'],
            ],
        ],
        [
            'nombre' => 'Pistones',
            'slug' => 'pistones',
            'descripcion' => 'Pistones y amortiguadores de gas',
            'atributos' => [
                'Fuerza' => ['60 N', '80 N', '100 N', '120 N', '150 N'],
                'Longitud' => ['245 mm', '260 mm', '270 mm', '300 mm'],
                'Acabado' => ['Gris satinado', 'Cromado', 'Negro'],
            ],
        ],
        [
            'nombre' => 'Cerraduras',
            'slug' => 'cerraduras',
            'descripcion' => 'Cerraduras y sistemas de cierre',
            'atributos' => [
                'Material' => ['Aleación de zinc', 'Acero inoxidable', 'Latón'],
                'Tamaño' => ['16 mm', '22 mm', '32 mm', '60 mm'],
            ],
        ],
    ];

    public function run(): void
    {
        // firstOrCreate permite ejecutar el seeder varias veces sin duplicar datos
        foreach (self::CATEGORIAS as $datos) {
            $categoria = Categoria::firstOrCreate(
                ['slug' => $datos['slug']],
                [
                    'nombre' => $datos['nombre'],
                    'descripcion' => $datos['descripcion'],
                ]
            );

            foreach ($datos['atributos'] as $atributo => $opciones) {
                $categoria->atributos()->updateOrCreate(
                    ['nombre' => $atributo],
                    ['opciones' => $opciones]
                );
            }
        }
    }
}
