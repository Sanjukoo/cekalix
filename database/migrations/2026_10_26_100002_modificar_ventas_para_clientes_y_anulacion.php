<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('cliente_id')
                ->nullable()
                ->after('fecha_venta')
                ->constrained('clientes')
                ->nullOnDelete();

            $table->string('estado', 15)->default('vigente')->after('total');
            $table->string('motivo_anulacion', 255)->nullable();

            $table->foreignId('anulado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });

        // Las ventas que tenían el cliente como texto pasan a registros de la tabla clientes.
        $nombres = DB::table('ventas')->whereNotNull('cliente')->distinct()->pluck('cliente');

        foreach ($nombres as $nombre) {
            $clienteId = DB::table('clientes')->insertGetId([
                'nombre' => $nombre,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('ventas')->where('cliente', $nombre)->update(['cliente_id' => $clienteId]);
        }

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('cliente');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('cliente', 100)->nullable()->after('fecha_venta');
        });

        DB::statement(
            'UPDATE ventas v JOIN clientes c ON c.id = v.cliente_id SET v.cliente = c.nombre'
        );

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cliente_id');
            $table->dropConstrainedForeignId('anulado_por');
            $table->dropColumn(['estado', 'motivo_anulacion']);
        });
    }
};
