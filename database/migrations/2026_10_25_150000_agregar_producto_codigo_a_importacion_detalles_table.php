<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El código usa importacion_detalles.producto_codigo (SKU facturado) y permite
     * producto_id NULL cuando el SKU aún no está registrado como producto.
     */
    public function up(): void
    {
        Schema::table('importacion_detalles', function (Blueprint $table) {
            $table->string('producto_codigo', 50)->nullable()->after('producto_id');
        });

        // Los detalles existentes (p. ej. los del seeder) heredan el código de su producto.
        DB::statement(
            'UPDATE importacion_detalles d
             JOIN productos p ON p.id = d.producto_id
             SET d.producto_codigo = p.codigo
             WHERE d.producto_codigo IS NULL'
        );

        Schema::table('importacion_detalles', function (Blueprint $table) {
            $table->unsignedBigInteger('producto_id')->nullable()->change();
            $table->unique(['importacion_id', 'producto_codigo'], 'imp_det_importacion_codigo_unique');
        });
    }

    public function down(): void
    {
        Schema::table('importacion_detalles', function (Blueprint $table) {
            $table->dropUnique('imp_det_importacion_codigo_unique');
        });

        // Los detalles sin producto no pueden existir con producto_id NOT NULL.
        DB::table('importacion_detalles')->whereNull('producto_id')->delete();

        Schema::table('importacion_detalles', function (Blueprint $table) {
            $table->unsignedBigInteger('producto_id')->nullable(false)->change();
            $table->dropColumn('producto_codigo');
        });
    }
};
