<?php

namespace App\Console\Commands;

use App\Services\ExternalDataMonitor;
use Illuminate\Console\Command;

class EvaluateExternalData extends Command
{
    protected $signature = 'external-data:evaluate';
    protected $description = 'Varsle om kritiske eksterne data og bekreftet gjenoppretting';

    public function handle(ExternalDataMonitor $monitor): int
    {
        $monitor->evaluate();
        return self::SUCCESS;
    }
}
