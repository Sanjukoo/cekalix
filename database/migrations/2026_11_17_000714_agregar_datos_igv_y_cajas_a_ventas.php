<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar los importes del IGV a las ventas.
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->default(0)->after('total');
            $table->decimal('igv', 12, 2)->default(0)->after('subtotal');
        });

        // Mantener los importes de las ventas anteriores.
        DB::table('ventas')->update([
            'subtotal' => DB::raw('total'),
            'igv' => 0,
        ]);

        // Guardar las cajas y unidades por caja en cada detalle.
        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->unsignedInteger('cajas')->nullable()->after('cantidad');
            $table->unsignedInteger('unidades_por_caja')->nullable()->after('cajas');
        });
    }

    public function down(): void
    {
        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->dropColumn(['cajas', 'unidades_por_caja']);
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'igv']);
        });
    }
};
