<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Solo agrega per_tutor_alumno.activo: un tutor con historial se desactiva en lugar de borrarse.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('per_tutor_alumno', function (Blueprint $tabla) {
            $tabla->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('per_tutor_alumno', function (Blueprint $tabla) {
            $tabla->dropColumn('activo');
        });
    }
};
