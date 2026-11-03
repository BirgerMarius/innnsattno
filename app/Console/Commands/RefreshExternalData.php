<?php

namespace App\Console\Commands;

use App\Services\ChampionsLeagueService;
use App\Services\BundesligaService;
use App\Services\ConferenceLeagueService;
use App\Services\EliteserienService;
use App\Services\EuropaLeagueService;
use App\Services\NationsLeagueService;
use App\Services\LaLigaService;
use App\Services\ObosLigaenService;
use App\Services\PremierLeagueService;
use App\Services\TvGuideService;
use App\Services\DwScheduleService;
use App\Services\FootballWorldCupService;
use App\Services\LocalNewsService;
use App\Services\NamedayService;
use App\Services\PrayerTimeService;
use App\Services\TodayContentService;
use App\Services\CorrectionalNewsService;
use App\Services\WeatherForecastService;
use App\Services\News\NewsFeedService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RefreshExternalData extends Command
{
    protected $signature = 'external-data:refresh {type? : Eldre scheduler-modus: tv eller football} {--force : Tving dagens TV-oppdatering i eldre scheduler-modus} {--all : Oppdater alle fengsler og felles datakilder} {--prison= : Oppdater ett fengsel (ringerike, ilseng eller kongsvinger)}';
    protected $description = 'Oppdater eksterne data for alle fengsler eller ett fengsel';

    public function handle(TvGuideService $tv): int
    {
        if (! Schema::hasTable('external_data_checks')) {
            $this->error('Migreringen for datakildekontroller mangler.');
            return self::FAILURE;
        }
        $type = $this->argument('type');
        $all = (bool) $this->option('all');
        $prison = $this->option('prison');

        if ($type !== null) {
            if ($all || $prison !== null) {
                $this->error('Eldre type-argument kan ikke kombineres med --all eller --prison.');
                return self::INVALID;
            }
            return $this->runLegacy((string) $type, $tv);
        }
        if ($all === ($prison !== null)) {
            $this->error('Velg nøyaktig ett av --all eller --prison=<fengsel>.');
            return self::INVALID;
        }
        if ($prison !== null && !array_key_exists($prison, config('prisons.all', []))) {
            $this->error('Ukjent fengsel: '.$prison.'.');
            return self::INVALID;
        }

        return $this->runManual($tv, $all ? array_keys(config('prisons.all', [])) : [(string) $prison], $all);
    }

    private function runLegacy(string $type, TvGuideService $tv): int
    {
        if ($type === 'tv') {
            $this->refreshTv($tv, (bool) $this->option('force'));
        } elseif ($type === 'football') {
            $this->refreshFootball();
        } else {
            $this->error('Ukjent datatype.');
            return self::FAILURE;
        }
        return self::SUCCESS;
    }

    private function runManual(TvGuideService $tv, array $prisons, bool $all): int
    {
        $rows = [];
        $failed = false;
        foreach ($prisons as $prison) {
            $config = config('prisons.all.'.$prison);
            $today = now('Europe/Oslo')->startOfDay();
            $ok = $this->attempt($rows, $prison, 'TV-guide (i dag)', fn () => $tv->refreshSchedule($today, $config['tv_channels'], $prison));
            $failed = $failed || !$ok;
            $ok = $this->attempt($rows, $prison, 'Lokale nyheter', fn () => app(LocalNewsService::class)->refresh($prison));
            $failed = $failed || !$ok;
            $ok = $this->attempt($rows, $prison, 'Værvarsel', fn () => app(WeatherForecastService::class)->refresh($config['weather_key']));
            $failed = $failed || !$ok;
            $locationId = $config['prayer_location_id'] ?? null;
            if (is_int($locationId) || ctype_digit((string) $locationId)) {
                $ok = $this->attempt($rows, $prison, 'Bønnetider (denne måneden)', fn () => app(PrayerTimeService::class)->refreshMonth((int) $locationId, $today->year, $today->month));
                $failed = $failed || !$ok;
            } else {
                $rows[] = [$prison, 'Bønnetider', 'Hoppet over: ingen verifisert steds-ID'];
            }
        }
        if ($all) {
            $failed = !$this->refreshCommon($rows) || $failed;
        } else {
            $rows[] = ['Felles', 'Fotball- og fellestjenester', 'Hoppet over ved fengselsvalg; bruk --all for én felles oppdatering'];
        }
        $this->table(['Omfang', 'Data', 'Resultat'], $rows);
        if ($failed) {
            $this->error('Oppdateringen er fullført med feil. Fungerende reservedata er beholdt der tjenestene støtter det.');
            return self::FAILURE;
        }
        $this->info('Oppdateringen er fullført. Ingen generell cache ble tømt.');
        return self::SUCCESS;
    }

    private function refreshCommon(array &$rows): bool
    {
        $ok = true;
        foreach ([PremierLeagueService::class, EliteserienService::class, ObosLigaenService::class, LaLigaService::class, BundesligaService::class, ChampionsLeagueService::class, EuropaLeagueService::class, ConferenceLeagueService::class, NationsLeagueService::class] as $class) {
            $service = app($class);
            $result = $this->attempt($rows, 'Felles', 'Fotball: '.$service->competitionKey(), fn () => $service->refreshCompetitionData());
            $ok = $result && $ok;
        }
        foreach (['schedule', 'standings'] as $endpoint) {
            $result = $this->attempt($rows, 'Felles', 'Fotball-VM: '.$endpoint, fn () => app(FootballWorldCupService::class)->refreshEndpoint($endpoint));
            $ok = $result && $ok;
        }
        $today = now('Europe/Oslo')->startOfDay();
        $ok = $this->attempt($rows, 'Felles', 'Navnedager', fn () => app(NamedayService::class)->refresh($today)) && $ok;
        $ok = $this->attempt($rows, 'Felles', 'Dagen i dag', fn () => app(TodayContentService::class)->forDate($today, true)) && $ok;
        $reports = app(CorrectionalNewsService::class)->refresh();
        foreach ($reports as $report) {
            $success = empty($report['error']);
            $rows[] = ['Felles', 'Kriminalomsorgsnyheter: '.$report['source'], $success ? 'Oppdatert' : 'Feil: '.$report['error']];
            $ok = $success && $ok;
        }
        $reports = app(NewsFeedService::class)->fetchActive();
        foreach ($reports as $report) {
            $success = empty($report['error']);
            $rows[] = ['Felles', 'Nyhetsfeed: '.$report['source'], $success ? 'Oppdatert' : 'Feil: '.$report['error']];
            $ok = $success && $ok;
        }
        return $ok;
    }

    private function attempt(array &$rows, string $scope, string $data, callable $operation): bool
    {
        try {
            $result = $operation();
            if ($result === false) {
                $rows[] = [$scope, $data, 'Feil eller opptatt; se datakildekontroller'];
                return false;
            }
            $rows[] = [$scope, $data, 'Oppdatert'];
            return true;
        } catch (\Throwable $exception) {
            $rows[] = [$scope, $data, 'Feil: '.class_basename($exception)];
            return false;
        }
    }

    private function refreshTv(TvGuideService $tv, bool $force = false): void
    {
        if ($force) {
            // A manual operational refresh must not silently succeed merely
            // because the scheduler's generic VG check is still within its
            // normal interval. Keep this deliberately narrow: it refreshes
            // today's published prison selections, not the whole window.
            $today = now('Europe/Oslo')->startOfDay();
            $failed = false;
            foreach (TvGuideService::prisonChannelSelections() as $selection => $channels) {
                $ok = $tv->refreshSchedule($today, $channels, $selection);
                $this->line('TV '.$selection.' '.$today->format('Y-m-d').': '.($ok ? 'kontrollert' : 'kunne ikke kontrolleres'));
                $failed = $failed || ! $ok;
            }
            if ($failed) {
                throw new \RuntimeException('Minst én tv-guide kunne ikke kontrolleres.');
            }

            return;
        }

        $dw = DB::table('external_data_checks')->where('service', 'dw-news')->first();
        if (! $dw || ! $dw->last_check_at || Carbon::parse($dw->last_check_at)->lte(now()->subMinutes($dw->failure_count ? 15 : 60))) {
            app(DwScheduleService::class)->refresh();
        }
        $today = now('Europe/Oslo')->startOfDay();
        // League TV boxes include next week's night to Monday; on a Monday that is fourteen days ahead.
        $start = $today->copy()->subDays(8);
        $end = $today->copy()->addDays(14);
        $checks = DB::table('external_data_checks')->where('service', 'tv-guide-vg')->get()->keyBy('coverage_key');
        $pending = [];
        $selections = TvGuideService::prisonChannelSelections();
        foreach ($selections as $selection => $channels) {
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                if ($tv->refreshDeferred($date, $channels, $selection) && $tv->hasCachedSchedule($date, $channels, $selection)) {
                    continue;
                }
                $key = $selection.'-'.$date->format('Y-m-d');
                $row = $checks[$key] ?? null;
                $interval = $row && $row->failure_count > 0 ? ((! $row->usable && $date->isSameDay($today)) ? 5 : 15) : 60;
                // The database records a source check, not the cache itself.
                // Rebuild a missing local value even while the source check is
                // still fresh, for example after a cache clear or a failed write.
                if (! $tv->hasCachedSchedule($date, $channels, $selection)
                    || ! $row || ! $row->last_check_at || Carbon::parse($row->last_check_at)->lte(now()->subMinutes($interval))) {
                    $pending[] = ['selection' => $selection, 'channels' => $channels, 'date' => $date->copy(), 'today' => $date->isSameDay($today), 'checked' => $row->last_check_at ?? ''];
                }
            }
        }
        // Continue confirming every previously alerted date, even across a week boundary.
        $incident = DB::table('external_data_incidents')->where('service', 'tv-guide-vg')->first();
        foreach ($incident ? (json_decode($incident->affected_keys, true) ?: []) : [] as $key) {
            if (! preg_match('/^([a-z0-9-]+)-(\d{4}-\d{2}-\d{2})$/', $key, $matches)) {
                continue;
            }
            $row = $checks[$key] ?? null;
            $date = Carbon::parse($matches[2], 'Europe/Oslo')->startOfDay();
            $channels = $selections[$matches[1]] ?? null;
            if ($channels === null) {
                continue;
            }
            if (! $row || ((int) $row->failure_count === 0 && $row->usable) || $date->betweenIncluded($start, $end)
                || ($tv->refreshDeferred($date, $channels, $matches[1]) && $tv->hasCachedSchedule($date, $channels, $matches[1]))
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
        foreach ([PremierLeagueService::class, EliteserienService::class, ObosLigaenService::class, LaLigaService::class, BundesligaService::class, ChampionsLeagueService::class, EuropaLeagueService::class, ConferenceLeagueService::class, NationsLeagueService::class] as $class) {
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
