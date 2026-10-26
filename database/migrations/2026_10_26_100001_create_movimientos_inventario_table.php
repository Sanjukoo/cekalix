<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Historial de cada cambio de stock. El stock de productos solo cambia a través de estos movimientos.
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete();

            // saldo_inicial, cierre_importacion, venta, anulacion_venta, edicion_venta, ajuste
            $table->string('tipo', 30);

            // Positivo entra al stock, negativo sale del stock.
            $table->integer('cantidad');

            // Stock del producto después de aplicar este movimiento.
            $table->integer('saldo');

            $table->string('motivo', 255)->nullable();

            // Documento que originó el movimiento (venta, importación, etc.).
            $table->string('referencia_tipo', 50)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();

            $table->foreignId('registrado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['producto_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
