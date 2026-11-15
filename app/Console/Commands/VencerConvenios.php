<?php

namespace App\Console\Commands;

use App\Models\OrgConvenio;
use Illuminate\Console\Command;

class VencerConvenios extends Command
{
    protected $signature = 'siges:vencer-convenios';

    protected $description = 'Pasa a VENCIDO los convenios vigentes cuya fecha de fin ya pasó (idempotente, con bitácora)';

    public function handle(): int
    {
        $vencidos = OrgConvenio::vencerPendientes(today());
        $this->info($vencidos === 0 ? 'Ningún convenio por vencer.' : "{$vencidos} ".($vencidos === 1 ? 'convenio pasó' : 'convenios pasaron').' a Vencido.');

        return self::SUCCESS;
    }
}
