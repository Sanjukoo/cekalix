<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    public function run(): void
    {
        $proveedores = [
            [
                'razon_social' => 'Proveedor Internacional A.',
                'identificador_wechat' => 'prov_internacional_a',
                'correo_electronico' => 'ventas@proveedorinternacional.com',
                'pais_origen' => 'China',
            ],
            [
                'razon_social' => 'Global Trade Corp.',
                'identificador_wechat' => 'globaltrade_corp',
                'correo_electronico' => 'contacto@globaltradecorp.com',
                'pais_origen' => 'China',
            ],
            [
                'razon_social' => 'Importadora del Pacífico',
                'identificador_wechat' => 'importadora_pacifico',
                'correo_electronico' => 'info@importadoradelpacifico.com',
                'pais_origen' => 'Taiwán',
            ],
        ];

        foreach ($proveedores as $datos) {
            $proveedor = Proveedor::firstOrCreate(
                ['razon_social' => $datos['razon_social']],
                $datos + ['activo' => true]
            );

            // Si el proveedor ya existía, solo se completan los campos vacíos;
            // nunca se pisan datos que el usuario haya editado.
            foreach ($datos as $campo => $valor) {
                if (blank($proveedor->$campo)) {
                    $proveedor->$campo = $valor;
                }
            }

            $proveedor->save();
        }
    }
}
