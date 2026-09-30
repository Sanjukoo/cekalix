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
            ['name'=>'Administrador', 'email'=>'admin@cekalix.com', 'role'=>'admin',],
            ['name'=>'Encargado de inventario', 'email'=>'inventario@cekalix.com', 'role'=>'inventario', ],
            ['name'=>'encargado de ventas', 'email'=>'ventas@cekalix.com', 'role'=>'ventas',]
        ];
        foreach($usuarios as $usuario) {
            User::updateOrCreate(['email'=>$usuario['email']], 
            [   'name'=>$usuario['name'],
                'role'=>$usuario['role'],
                'password'=>Hash::make('password'),
            ]
            );
        }
    }
}
