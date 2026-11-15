<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Carga los datos iniciales de SIGEs: catálogos, usuario Sistema,
     * roles, permisos y permisos de cada rol.
     * El archivo es seguro de ejecutar varias veces: no duplica registros.
     */
    public function run(): void
    {
        DB::unprepared(
            file_get_contents(database_path('seeders/sql/siges_datos_iniciales.sql'))
        );

        $this->call(OrgAreaSeeder::class);

        // Spatie guarda los permisos en caché; se limpia para que tome los nuevos.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
