<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reemplaza el texto libre "proveedor" por una relación con la tabla proveedores.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('proveedor_id')
                ->nullable()
                ->after('categoria_id')
                ->constrained('proveedores')
                ->onDelete('restrict');
        });

        // Vincular los productos existentes: cada nombre de proveedor escrito a mano
        // se busca en la tabla proveedores y, si no existe, se crea.
        $nombres = DB::table('productos')->distinct()->pluck('proveedor');

        foreach ($nombres as $nombre) {
            $proveedorId = DB::table('proveedores')->where('nombre', $nombre)->value('id')
                ?? DB::table('proveedores')->insertGetId([
                    'nombre' => $nombre,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('productos')
                ->where('proveedor', $nombre)
                ->update(['proveedor_id' => $proveedorId]);
        }

        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('proveedor_id')->nullable(false)->change();
            $table->dropColumn('proveedor');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('proveedor', 100)->nullable()->after('categoria_id');
        });

        DB::table('productos')
            ->join('proveedores', 'proveedores.id', '=', 'productos.proveedor_id')
            ->update(['productos.proveedor' => DB::raw('proveedores.nombre')]);

        Schema::table('productos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proveedor_id');
        });
    }
};
