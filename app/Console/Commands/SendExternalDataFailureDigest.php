<?php

namespace App\Console\Commands;

use App\Services\ExternalDataMonitor;
use Illuminate\Console\Command;

class SendExternalDataFailureDigest extends Command
{
    protected $signature = 'external-data:send-failure-digest';
    protected $description = 'Bakoverkompatibel kommando for vurdering av kritiske datakilfeil';

    public function handle(ExternalDataMonitor $service): int
    {
        $service->evaluate();
        $this->info('Kritiske datakilder er vurdert.');
        return self::SUCCESS;
    }
}
