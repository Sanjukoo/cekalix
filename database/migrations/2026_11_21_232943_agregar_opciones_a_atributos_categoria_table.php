<?php

use Database\Seeders\CategoriaSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lista de valores posibles de cada atributo (se muestra como lista desplegable).
        Schema::table('atributos_categoria', function (Blueprint $table) {
            $table->json('opciones')->nullable()->after('nombre');
        });

        // Completar con las opciones de ejemplo los atributos de las categorías que ya existen.
        foreach (CategoriaSeeder::CATEGORIAS as $categoria) {
            $categoriaId = DB::table('categorias')->where('slug', $categoria['slug'])->value('id');

            if (!$categoriaId) {
                continue;
            }

            foreach ($categoria['atributos'] as $nombre => $opciones) {
                DB::table('atributos_categoria')
                    ->where('categoria_id', $categoriaId)
                    ->where('nombre', $nombre)
                    ->update(['opciones' => json_encode($opciones, JSON_UNESCAPED_UNICODE)]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('atributos_categoria', function (Blueprint $table) {
            $table->dropColumn('opciones');
        });
    }
};
