<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();

            // Sin cliente registrado se muestra "Cliente general".
            $table->string('cliente', 100)->nullable();

            $table->date('fecha_venta');

            $table->decimal('total', 12, 2)->default(0);

            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
