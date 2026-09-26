<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 100);
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('restrict');
            $table->string('proveedor', 100);
            $table->integer('stock')->unsigned()->default(0);
            $table->integer('unidades_por_caja')->unsigned();
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index('codigo');
            $table->index('categoria_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
