<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('restrict');
            $table->string('numero_factura', 50);
            $table->string('numero_contenedor', 50);
            $table->date('fecha_llegada');
            $table->string('estado', 30)->default('En recepción');
            $table->timestamps();

            $table->index('proveedor_id');
            $table->index('fecha_llegada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importaciones');
    }
};
