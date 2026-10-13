<?php

namespace App\Console\Commands;

use App\Services\ExternalDataFailureDigestService;
use Illuminate\Console\Command;

class PruneExternalDataSourceEvents extends Command
{
    protected $signature = 'external-data:prune-events';
    protected $description = 'Fjerner gammel historikk for eksterne datakildefeil';

    public function handle(ExternalDataFailureDigestService $service): int
    {
        $this->info('Fjernet '.$service->prune().' gamle datakildehendelser.');
        return self::SUCCESS;
    }
}
