<?php

namespace App\Console\Commands;

use App\Services\ExternalDataFailureDigestService;
use Illuminate\Console\Command;

class SendExternalDataFailureDigest extends Command
{
    protected $signature = 'external-data:send-failure-digest';
    protected $description = 'Sender samlet døgnoversikt over eksterne datakildefeil';

    public function handle(ExternalDataFailureDigestService $service): int
    {
        $sent = $service->send();
        $this->info($sent ? 'Feiloversikt sendt.' : 'Ingen ny feiloversikt å sende.');
        return self::SUCCESS;
    }
}
