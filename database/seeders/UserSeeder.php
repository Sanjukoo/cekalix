<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            ['name' => 'Administrador', 'email' => 'admin@cekalix.com', 'role' => 'admin'],
            ['name' => 'Encargado de inventario', 'email' => 'inventario@cekalix.com', 'role' => 'inventario'],
            ['name' => 'Encargado de ventas', 'email' => 'ventas@cekalix.com', 'role' => 'ventas'],
        ];

        // La contraseña solo se asigna al crear el usuario; volver a ejecutar
        // el seeder no reemplaza una contraseña que ya se haya cambiado.
        foreach ($usuarios as $usuario) {
            User::firstOrCreate(
                ['email' => $usuario['email']],
                [
                    'name' => $usuario['name'],
                    'role' => $usuario['role'],
                    'password' => Hash::make('password'),
                ]
            );
        }
    }
}
