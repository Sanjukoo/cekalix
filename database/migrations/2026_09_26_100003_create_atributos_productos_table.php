<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atributos_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->string('clave', 50);
            $table->string('valor', 100);
            $table->timestamps();

            $table->unique(['producto_id', 'clave']);
            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atributos_productos');
    }
};
