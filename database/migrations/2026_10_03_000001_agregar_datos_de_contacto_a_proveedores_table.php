<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajusta proveedores al diagrama: razón social, WeChat, correo y país de origen.
     * Los campos nuevos son opcionales en la base para no romper los proveedores
     * que ya existen; el formulario es el que los exige.
     */
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->renameColumn('nombre', 'razon_social');
        });

        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('identificador_wechat', 100)->nullable()->after('razon_social');
            $table->string('correo_electronico', 150)->nullable()->after('identificador_wechat');
            $table->string('pais_origen', 100)->nullable()->after('correo_electronico');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn(['identificador_wechat', 'correo_electronico', 'pais_origen']);
        });

        Schema::table('proveedores', function (Blueprint $table) {
            $table->renameColumn('razon_social', 'nombre');
        });
    }
};
