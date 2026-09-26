<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        Categoria::create([
            'nombre' => 'Correderas',
            'slug' => 'correderas',
            'descripcion' => 'Sistemas de correderas para muebles y puertas',
        ]);

        Categoria::create([
            'nombre' => 'Bisagras',
            'slug' => 'bisagras',
            'descripcion' => 'Bisagras y herrajes para puertas y muebles',
        ]);

        Categoria::create([
            'nombre' => 'Pistones',
            'slug' => 'pistones',
            'descripcion' => 'Pistones y amortiguadores de gas',
        ]);

        Categoria::create([
            'nombre' => 'Cerraduras',
            'slug' => 'cerraduras',
            'descripcion' => 'Cerraduras y sistemas de cierre',
        ]);
    }
}
