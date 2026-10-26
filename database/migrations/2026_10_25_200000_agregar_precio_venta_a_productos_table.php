<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Precio por unidad. Los productos existentes quedan en 0 hasta que el seeder les asigne su precio.
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('precio_venta', 10, 2)->unsigned()->default(0)->after('unidades_por_caja');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('precio_venta');
        });
    }
};
