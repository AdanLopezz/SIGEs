<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('com_notificacion', function (Blueprint $tabla) {
            $tabla->foreignId('trx_incidencia_id')->nullable()->constrained('trx_incidencia')->restrictOnDelete();
            $tabla->index('trx_incidencia_id');
        });
    }

    public function down(): void
    {
        Schema::table('com_notificacion', function (Blueprint $tabla) {
            $tabla->dropForeign(['trx_incidencia_id']);
            $tabla->dropIndex(['trx_incidencia_id']);
            $tabla->dropColumn('trx_incidencia_id');
        });
    }
};
