<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('importacion_id')->constrained('importaciones')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('restrict');
            $table->integer('cajas_facturadas')->unsigned();
            $table->timestamps();

            $table->unique(['importacion_id', 'producto_id']);
            $table->index('importacion_id');
            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importacion_detalles');
    }
};
