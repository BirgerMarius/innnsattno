<?php

namespace App\Console\Commands;

use App\Services\ChampionsLeagueService;
use App\Services\ConferenceLeagueService;
use App\Services\EliteserienService;
use App\Services\EuropaLeagueService;
use App\Services\NationsLeagueService;
use App\Services\PremierLeagueService;
use App\Services\TvGuideService;
use App\Services\DwScheduleService;
use App\Services\FootballWorldCupService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RefreshExternalData extends Command
{
    protected $signature = 'external-data:refresh {type : tv or football}';
    protected $description = 'Kontroller og oppdater lokal TV- og fotballcache';

    public function handle(TvGuideService $tv): int
    {
        if (! Schema::hasTable('external_data_checks')) {
            $this->error('Migreringen for datakildekontroller mangler.');
            return self::FAILURE;
        }
        if ($this->argument('type') === 'tv') {
            $this->refreshTv($tv);
        } elseif ($this->argument('type') === 'football') {
            $this->refreshFootball();
        } else {
            $this->error('Ukjent datatype.');
            return self::FAILURE;
        }
        return self::SUCCESS;
    }

    private function refreshTv(TvGuideService $tv): void
    {
        $dw = DB::table('external_data_checks')->where('service', 'dw-news')->first();
        if (! $dw || ! $dw->last_check_at || Carbon::parse($dw->last_check_at)->lte(now()->subMinutes($dw->failure_count ? 15 : 60))) {
            app(DwScheduleService::class)->refresh();
        }
        $today = now('Europe/Oslo')->startOfDay();
        // Admin print supports eight days back and thirteen ahead; this also covers both weekly views.
        $start = $today->copy()->subDays(8);
        $end = $today->copy()->addDays(13);
        $checks = DB::table('external_data_checks')->where('service', 'tv-guide-vg')->get()->keyBy('coverage_key');
        $pending = [];
        foreach (['ringerike' => TvGuideService::ringerikeChannels(), 'ilseng' => TvGuideService::ilsengChannels()] as $selection => $channels) {
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                if ($tv->refreshDeferred($date, $channels)) {
                    continue;
                }
                $key = $selection.'-'.$date->format('Y-m-d');
                $row = $checks[$key] ?? null;
                $interval = $row && $row->failure_count > 0 ? ((! $row->usable && $date->isSameDay($today)) ? 5 : 15) : 60;
                if (! $row || ! $row->last_check_at || Carbon::parse($row->last_check_at)->lte(now()->subMinutes($interval))) {
                    $pending[] = ['selection' => $selection, 'channels' => $channels, 'date' => $date->copy(), 'today' => $date->isSameDay($today), 'checked' => $row->last_check_at ?? ''];
                }
            }
        }
        // Continue confirming every previously alerted date, even across a week boundary.
        $incident = DB::table('external_data_incidents')->where('service', 'tv-guide-vg')->first();
        foreach ($incident ? (json_decode($incident->affected_keys, true) ?: []) : [] as $key) {
            if (! preg_match('/^(ringerike|ilseng)-(\d{4}-\d{2}-\d{2})$/', $key, $matches)) {
                continue;
            }
            $row = $checks[$key] ?? null;
            $date = Carbon::parse($matches[2], 'Europe/Oslo')->startOfDay();
            $channels = $matches[1] === 'ilseng' ? TvGuideService::ilsengChannels() : TvGuideService::ringerikeChannels();
            if (! $row || ((int) $row->failure_count === 0 && $row->usable) || $date->betweenIncluded($start, $end)
                || $tv->refreshDeferred($date, $channels)
                || ($row->last_check_at && Carbon::parse($row->last_check_at)->gt(now()->subMinutes(15)))) {
                continue;
            }
            $pending[] = [
                'selection' => $matches[1],
                'channels' => $channels,
                'date' => $date, 'today' => false, 'checked' => $row->last_check_at ?? '',
            ];
        }
        usort($pending, fn ($a, $b) => ($b['today'] <=> $a['today']) ?: strcmp($a['checked'], $b['checked']));
        foreach (array_slice($pending, 0, max(1, (int) config('external_data.tv_batch_size', 8))) as $item) {
            $tv->refreshSchedule($item['date'], $item['channels'], $item['selection']);
        }
    }

    private function refreshFootball(): void
    {
        $checks = DB::table('external_data_checks')->where('service', 'sportsnext')->get()->keyBy('coverage_key');
        foreach ([PremierLeagueService::class, EliteserienService::class, ChampionsLeagueService::class, EuropaLeagueService::class, ConferenceLeagueService::class, NationsLeagueService::class] as $class) {
            $service = app($class);
            $key = $service->competitionKey();
            $row = $checks[$key] ?? null;
            $live = collect($service->getCompetitionData()['matches'] ?? [])->contains(function ($match) {
                $start = $match['startsAt'] ?? null;
                return $start instanceof Carbon && $start->betweenIncluded(now('Europe/Oslo')->subHours(3), now('Europe/Oslo')->addMinutes(15));
            });
            $interval = $row && $row->failure_count > 0 ? ($row->usable ? 15 : 5) : ($live ? 5 : 30);
            if (! $row || ! $row->last_check_at || Carbon::parse($row->last_check_at)->lte(now()->subMinutes($interval))) {
                $service->refreshCompetitionData();
            }
        }
        $worldCup = app(FootballWorldCupService::class);
        $worldCupLive = collect($worldCup->getData()['schedule']['events'] ?? [])->contains(function ($event) {
            try {
                $start = Carbon::parse($event['startDate'] ?? null);
                return ! empty($event['startDate']) && $start->betweenIncluded(now()->subHours(3), now()->addMinutes(15));
            } catch (\Throwable $exception) {
                return false;
            }
        });
        foreach (['schedule', 'standings'] as $endpoint) {
            $row = $checks['world-cup-'.$endpoint] ?? null;
            $interval = $row && $row->failure_count > 0 ? ($row->usable ? 15 : 5) : ($worldCupLive ? 5 : 30);
            if (! $row || ! $row->last_check_at || Carbon::parse($row->last_check_at)->lte(now()->subMinutes($interval))) {
                $worldCup->refreshEndpoint($endpoint);
            }
        }
    }
}
