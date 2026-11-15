<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Contraseña temporal generada por el sistema: cifrada (Crypt) mientras el usuario no la cambie,
// y la marca de cambio obligatorio al primer ingreso.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $tabla) {
            $tabla->text('password_temporal')->nullable();
            $tabla->boolean('debe_cambiar_password')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $tabla) {
            $tabla->dropColumn(['password_temporal', 'debe_cambiar_password']);
        });
    }
};
