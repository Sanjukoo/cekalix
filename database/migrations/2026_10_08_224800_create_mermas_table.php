
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mermas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('importacion_detalle_id')
                ->constrained('importacion_detalles')
                ->cascadeOnDelete();

            $table->unsignedInteger('cantidad');

            $table->string('motivo');

            $table->date('fecha_registro');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mermas');
    }
};