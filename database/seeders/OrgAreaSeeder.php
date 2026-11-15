<?php

namespace Database\Seeders;

use App\Models\OrgArea;
use Illuminate\Database\Seeder;

class OrgAreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = ['Dirección', 'Subdirección Académica', 'Control Escolar', 'Docencia', 'Orientación Educativa',
            'Vinculación', 'Prefectura', 'Planeación', 'Servicios Administrativos'];
        OrgArea::upsert(array_map(fn ($nombre) => ['nombre' => $nombre], $areas), ['nombre'], ['nombre']);
    }
}
