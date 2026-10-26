<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Antes del kárdex no había historial. Cada producto con stock recibe un
     * movimiento de saldo inicial con su stock actual, para que el historial arranque desde ahí.
     */
    public function up(): void
    {
        $productos = DB::table('productos')->where('stock', '>', 0)->get(['id', 'stock']);

        foreach ($productos as $producto) {
            DB::table('movimientos_inventario')->insert([
                'producto_id' => $producto->id,
                'tipo' => 'saldo_inicial',
                'cantidad' => $producto->stock,
                'saldo' => $producto->stock,
                'motivo' => 'Saldo inicial al implementar el kárdex',
                'registrado_por' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('movimientos_inventario')->where('tipo', 'saldo_inicial')->delete();
    }
};
