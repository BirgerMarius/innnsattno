<?php

namespace App\Console\Commands;

use App\Mail\WeeklyStatisticsReportMail;
use App\Services\WeeklyStatisticsReport;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PreviewWeeklyStatisticsReport extends Command
{
    protected $signature = 'statistics:weekly-report:preview {week : Mandagen som starter uken (YYYY-MM-DD)} {--output= : Fil for HTML-forhåndsvisning}';
    protected $description = 'Genererer ukesrapport lokalt uten e-postsending';

    public function handle(WeeklyStatisticsReport $reports): int
    {
        $report = $reports->forWeek(Carbon::parse($this->argument('week'), 'Europe/Oslo'));
        if (! $report) { $this->error('Fant ikke en gyldig rapport for uken.'); return self::FAILURE; }
        $html = (new WeeklyStatisticsReportMail($report))->render();
        if ($path = $this->option('output')) { file_put_contents($path, $html); $this->info('Skrev '.$path); } else { $this->line($html); }
        return self::SUCCESS;
    }
}
