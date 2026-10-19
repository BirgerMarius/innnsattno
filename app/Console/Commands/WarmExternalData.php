<?php

namespace App\Console\Commands;

use App\Services\ChampionsLeagueService;
use App\Services\ConferenceLeagueService;
use App\Services\EliteserienService;
use App\Services\EuropaLeagueService;
use App\Services\FootballWorldCupService;
use App\Services\NationsLeagueService;
use App\Services\PremierLeagueService;
use App\Services\TvGuideService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class WarmExternalData extends Command
{
    protected $signature = 'external-data:warm';
    protected $description = 'Forhåndshent TV- og fotballdata og rapporter datadekning';

    public function handle(TvGuideService $tv): int
    {
        if (! Schema::hasTable('external_data_checks')) {
            $this->error('Kjør databasemigreringene før forhåndshenting.');
            return self::FAILURE;
        }

        $rows = [];
        $failed = false;
        $today = now('Europe/Oslo')->startOfDay();
        $dates = [$today->copy()];
        for ($i = 1; $i <= 8; $i++) $dates[] = $today->copy()->subDays($i);
        for ($i = 1; $i <= 13; $i++) $dates[] = $today->copy()->addDays($i);
        foreach ($dates as $date) {
            foreach (['ringerike' => TvGuideService::ringerikeChannels(), 'ilseng' => TvGuideService::ilsengChannels()] as $selection => $channels) {
                $ok = $tv->refreshSchedule($date, $channels, $selection);
                $available = collect($tv->getSchedule($date, $channels, 'warm'))->contains(fn ($channel) => ! empty($channel['listings']));
                $status = ! $ok ? ($available ? 'Feil, reservedata beholdt' : 'Feil, data mangler')
                    : ($tv->refreshDeferred($date, $channels) ? 'Reservedata beholdt; tomt kildesvar' : ($available ? 'Oppdatert' : ($date->gt($today) ? 'Ingen programmer publisert' : 'Data mangler')));
                $rows[] = [$selection.' '.$date->toDateString(), $status];
                $failed = $failed || ! $ok || (! $date->gt($today) && ! $available);
            }
        }
        foreach ([PremierLeagueService::class, EliteserienService::class, ChampionsLeagueService::class, EuropaLeagueService::class, ConferenceLeagueService::class, NationsLeagueService::class] as $class) {
            $service = app($class);
            $ok = $service->refreshCompetitionData();
            $data = $service->getCompetitionData();
            $available = ! empty($data['sourceFetchedAt']) || ! empty($data['matches']) || ! empty($data['standings']) || ! empty($data['upcomingFixtures']);
            $rows[] = [$service->competitionKey(), $ok ? 'Oppdatert' : ($available ? 'Feil eller låst; reservedata beholdt' : 'Feil eller låst; data mangler')];
            $failed = $failed || ! $ok;
        }
        foreach (['schedule', 'standings'] as $endpoint) {
            $worldCup = app(FootballWorldCupService::class);
            $ok = $worldCup->refreshEndpoint($endpoint);
            $data = $worldCup->getData()[$endpoint];
            $available = ! empty($data[$endpoint === 'schedule' ? 'events' : 'standings']);
            $rows[] = ['world-cup-'.$endpoint, $ok ? 'Oppdatert' : ($available ? 'Feil eller låst; reservedata beholdt' : 'Feil eller låst; data mangler')];
            $failed = $failed || ! $ok;
        }
        $this->table(['Datadekning', 'Resultat'], $rows);
        if ($failed) {
            $this->error('Noen hentinger feilet eller mangler data. Scheduleren prøver på nytt.');
            return self::FAILURE;
        }
        $this->info('Forhåndshenting fullført.');
        return self::SUCCESS;
    }
}
