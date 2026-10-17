<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conteos_fisicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('importacion_detalle_id')->unique()
                ->constrained('importacion_detalles')->cascadeOnDelete();
            $table->unsignedInteger('cajas_contadas');
            $table->bigInteger('diferencia');
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conteos_fisicos');
    }
};
