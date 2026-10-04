<?php

namespace App\Console\Commands;

use App\Services\FangenyttArchive;
use Illuminate\Console\Command;

class GenerateFangenyttCovers extends Command
{
    protected $signature = 'fangenytt:covers {--force : Generer også eksisterende forsider på nytt}';
    protected $description = 'Genererer forsider for lokale Fangenytt-PDF-er';

    public function handle(FangenyttArchive $archive): int
    {
        $reports = $archive->generateCovers((bool) $this->option('force'));

        $this->table(
            ['Utgave', 'Status', 'Detalj'],
            array_map(fn (array $report) => [$report['number'], $report['status'], $report['message']], $reports)
        );

        return collect($reports)->contains(fn (array $report) => $report['status'] === 'feilet') ? self::FAILURE : self::SUCCESS;
    }
}
