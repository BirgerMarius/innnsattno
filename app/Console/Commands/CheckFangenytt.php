<?php

namespace App\Console\Commands;

use App\Services\FangenyttCheck;
use Illuminate\Console\Command;

class CheckFangenytt extends Command
{
    protected $signature = 'fangenytt:check {--force : Kontroller uavhengig av intervallet}';
    protected $description = 'Kontrollerer og publiserer nye Fangenytt-utgaver';

    public function handle(FangenyttCheck $check): int
    {
        $result = $check->run((bool) $this->option('force'));
        $this->line($result['message']);
        return $result['success'] ? 0 : 1;
    }
}
