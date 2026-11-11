<?php

namespace App\Console\Commands;

use App\Services\SportsNextSeasonControlService;
use Illuminate\Console\Command;

class ControlSportsNextSeasons extends Command
{
    protected $signature = 'sportsnext:season-control {--competition= : Kontroller bare én nøkkel}';
    protected $description = 'Kontroller SportsNext-sesonger og bytt bare når alle strenge vilkår er oppfylt';
    public function handle(SportsNextSeasonControlService $control): int
    {
        $key = $this->option('competition');
        $rows = $key ? [$control->check($key)] : $control->checkAll();
        foreach ($rows as $row) $this->line(($row['status'] ?? 'Feil').': '.($row['reason'] ?? ''));
        return self::SUCCESS;
    }
}
