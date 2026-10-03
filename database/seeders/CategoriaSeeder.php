<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Correderas',
                'slug' => 'correderas',
                'descripcion' => 'Sistemas de correderas para muebles y puertas',
                'atributos' => ['Longitud', 'Espesor', 'Ancho', 'Color'],
            ],
            [
                'nombre' => 'Bisagras',
                'slug' => 'bisagras',
                'descripcion' => 'Bisagras y herrajes para puertas y muebles',
                'atributos' => ['Tipo', 'Acabado', 'Peso'],
            ],
            [
                'nombre' => 'Pistones',
                'slug' => 'pistones',
                'descripcion' => 'Pistones y amortiguadores de gas',
                'atributos' => ['Fuerza', 'Longitud', 'Acabado'],
            ],
            [
                'nombre' => 'Cerraduras',
                'slug' => 'cerraduras',
                'descripcion' => 'Cerraduras y sistemas de cierre',
                'atributos' => ['Material', 'Tamaño'],
            ],
        ];

        // firstOrCreate permite ejecutar el seeder varias veces sin duplicar datos
        foreach ($categorias as $datos) {
            $categoria = Categoria::firstOrCreate(
                ['slug' => $datos['slug']],
                [
                    'nombre' => $datos['nombre'],
                    'descripcion' => $datos['descripcion'],
                ]
            );

            foreach ($datos['atributos'] as $atributo) {
                $categoria->atributos()->firstOrCreate(['nombre' => $atributo]);
            }
        }
    }
}
