<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Solo índices para las consultas de la Bitácora; no cambia columnas.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_auditoria', function (Blueprint $tabla) {
            $tabla->index('created_at', 'sys_auditoria_created_at_index');
            $tabla->index(['tabla_afectada', 'registro_id'], 'sys_auditoria_tabla_registro_index');
            $tabla->index('user_id', 'sys_auditoria_user_id_index');
        });
        Schema::table('sys_auditoria_detalle', function (Blueprint $tabla) {
            $tabla->index('sys_auditoria_id', 'sys_auditoria_detalle_sys_auditoria_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('sys_auditoria_detalle', function (Blueprint $tabla) {
            $tabla->dropIndex('sys_auditoria_detalle_sys_auditoria_id_index');
        });
        Schema::table('sys_auditoria', function (Blueprint $tabla) {
            $tabla->dropIndex('sys_auditoria_user_id_index');
            $tabla->dropIndex('sys_auditoria_tabla_registro_index');
            $tabla->dropIndex('sys_auditoria_created_at_index');
        });
    }
};
