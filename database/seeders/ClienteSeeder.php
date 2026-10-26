<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        // Datos ficticios para pruebas. Se busca por nombre para que volver a ejecutar el seeder no duplique clientes.
        $clientes = [
            [
                'nombre' => 'Mueblería El Roble',
                'tipo_documento' => 'RUC',
                'numero_documento' => '20111111111',
                'telefono' => '987654321',
                'direccion' => 'Av. Los Carpinteros 123, Lima',
            ],
            [
                'nombre' => 'Carlos Huamán Quispe',
                'tipo_documento' => 'DNI',
                'numero_documento' => '40123456',
                'telefono' => '912345678',
                'direccion' => 'Jr. Las Flores 456, Lima',
            ],
            [
                'nombre' => 'Constructora Andina S.A.C.',
                'tipo_documento' => 'RUC',
                'numero_documento' => '20222222222',
                'telefono' => '014567890',
                'direccion' => 'Calle Industrial 789, Callao',
            ],
        ];

        foreach ($clientes as $cliente) {
            Cliente::updateOrCreate(['nombre' => $cliente['nombre']], $cliente);
        }
    }
}
