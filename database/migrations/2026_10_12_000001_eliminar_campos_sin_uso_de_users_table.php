<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina campos de users que el proyecto no usa:
     * - email_verified_at: la verificación de correo está desactivada en Fortify.
     * - remember_token: el login no ofrece la opción "Recordarme".
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_verified_at', 'remember_token']);
        });
    }

    /**
     * Los vuelve a crear con la estructura original de Laravel.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->rememberToken()->after('role');
        });
    }
};
