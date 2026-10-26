<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 100);

            // DNI, RUC, CE u otro documento. Opcional: no todas las ventas lo requieren.
            $table->string('tipo_documento', 10)->nullable();
            $table->string('numero_documento', 20)->nullable()->unique();

            $table->string('telefono', 20)->nullable();
            $table->string('direccion', 255)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
