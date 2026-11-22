<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// El número de contenedor dejó de usarse en las importaciones.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('importaciones', 'numero_contenedor')) {
            Schema::table('importaciones', function (Blueprint $table) {
                $table->dropColumn('numero_contenedor');
            });
        }
    }

    public function down(): void
    {
        // Se restaura como opcional: los valores eliminados no se recuperan.
        Schema::table('importaciones', function (Blueprint $table) {
            $table->string('numero_contenedor', 50)->nullable()->after('numero_factura');
        });
    }
};
