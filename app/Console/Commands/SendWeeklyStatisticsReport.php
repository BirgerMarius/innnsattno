<?php

namespace App\Console\Commands;

use App\Mail\WeeklyStatisticsReportMail;
use App\Services\WeeklyStatisticsReport;
use App\WeeklyStatisticsReportDelivery;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWeeklyStatisticsReport extends Command
{
    protected $signature = 'statistics:weekly-report {--week= : Mandagen som starter uken (YYYY-MM-DD)}';
    protected $description = 'Sender forrige fullførte kalenderukes statistikkrapport når datagrunnlaget er klart';

    public function handle(WeeklyStatisticsReport $reports): int
    {
        if (! config('weekly_statistics.enabled')) {
            $this->line('Ukentlig statistikkrapport er deaktivert.');
            return self::SUCCESS;
        }
        $now = now('Europe/Oslo');
        $week = $this->option('week') ? Carbon::parse($this->option('week'), 'Europe/Oslo')->startOfDay() : $now->copy()->startOfWeek()->subWeek();
        $lock = Cache::lock('weekly-statistics-report:'.$week->toDateString(), 300);
        if (! $lock->get()) {
            return self::SUCCESS;
        }
        try {
            $report = $reports->forWeek($week);
            if (! $report || ! $report['ready']) {
                $this->line('Venter på komplett statistikk for '.$week->toDateString().'.');
                return self::SUCCESS;
            }
            $delivery = WeeklyStatisticsReportDelivery::firstOrCreate(['week_starts_on' => $week->toDateString()]);
            if ($delivery->sent_at) {
                return self::SUCCESS;
            }
            $delivery->increment('attempts', 1, ['last_attempt_at' => now(), 'last_error' => null]);
            try {
                Mail::to(config('weekly_statistics.recipient'))->send(new WeeklyStatisticsReportMail($report));
                $delivery->forceFill(['sent_at' => now(), 'last_error' => null])->save();
                $this->info('Ukentlig statistikkrapport sendt.');
                return self::SUCCESS;
            } catch (\Throwable $exception) {
                $delivery->forceFill(['last_error' => mb_substr($exception->getMessage(), 0, 2000)])->save();
                Log::warning('Kunne ikke sende ukentlig statistikkrapport.', ['week' => $week->toDateString(), 'exception' => $exception]);
                $this->error('Sending feilet; scheduleren prøver igjen.');
                return self::FAILURE;
            }
        } finally {
            optional($lock)->release();
        }
    }
}
