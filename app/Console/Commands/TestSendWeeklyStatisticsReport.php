<?php

namespace App\Console\Commands;

use App\Mail\WeeklyStatisticsReportMail;
use App\Services\WeeklyStatisticsReport;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class TestSendWeeklyStatisticsReport extends Command
{
    protected $signature = 'statistics:weekly-report:test-send {week : Mandagen som starter uken (YYYY-MM-DD)} {--to=innsatt@innsatt.no : Eksplisitt mottaker}';
    protected $description = 'Sender en uttrykkelig testsending; påvirker ikke ordinær sendestatus';
    public function handle(WeeklyStatisticsReport $reports): int
    {
        $report = $reports->forWeek(Carbon::parse($this->argument('week'), 'Europe/Oslo'));
        if (! $report) { $this->error('Fant ikke en gyldig rapport for uken.'); return self::FAILURE; }
        Mail::to($this->option('to'))->send(new WeeklyStatisticsReportMail($report));
        $this->info('Testsending bekreftet av e-posttransporten.');
        return self::SUCCESS;
    }
}
