<?php

namespace App\Console\Commands;

use App\Services\FangenyttArchive;
use Illuminate\Console\Command;

class SyncFangenytt extends Command
{
    protected $signature = 'fangenytt:sync {--force : Erstatt også eksisterende lokale PDF-filer}';
    protected $description = 'Henter registrerte Fangenytt-PDF-er til lokalt arkiv';

    public function handle(FangenyttArchive $archive): int
    {
        $reports = $archive->sync((bool) $this->option('force'));

        $this->table(
            ['Utgave', 'Status', 'Detalj'],
            array_map(fn (array $report) => [$report['number'], $report['status'], $report['message']], $reports)
        );

        return collect($reports)->contains(fn (array $report) => $report['status'] === 'feilet') ? self::FAILURE : self::SUCCESS;
    }
}
