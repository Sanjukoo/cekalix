<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    public function run(): void
    {
        Proveedor::create([
            'nombre' => 'Proveedor Internacional A.',
            'activo' => true,
        ]);

        Proveedor::create([
            'nombre' => 'Global Trade Corp.',
            'activo' => true,
        ]);

        Proveedor::create([
            'nombre' => 'Importadora del Pacífico',
            'activo' => true,
        ]);
    }
}
