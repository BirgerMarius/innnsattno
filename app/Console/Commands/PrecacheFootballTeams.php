<?php

namespace App\Console\Commands;

use App\Services\ChampionsLeagueService;
use App\Services\ConferenceLeagueService;
use App\Services\EliteserienService;
use App\Services\EuropaLeagueService;
use App\Services\NationsLeagueService;
use App\Services\PremierLeagueService;
use Illuminate\Console\Command;

class PrecacheFootballTeams extends Command
{
    protected $signature = 'football:precache-teams';

    protected $description = 'Oppdater SportsNext-lagdata og logoer som brukes av Fotball på TV.';

    public function handle(
        PremierLeagueService $premierLeague,
        EliteserienService $eliteserien,
        ChampionsLeagueService $championsLeague,
        EuropaLeagueService $europaLeague,
        ConferenceLeagueService $conferenceLeague,
        NationsLeagueService $nationsLeague,
    ): int {
        foreach ([$premierLeague, $eliteserien, $championsLeague, $europaLeague, $conferenceLeague, $nationsLeague] as $service) {
            $data = $service->getCompetitionData();
            $this->line($service->competitionNameForCache().': '.count($data['teams'] ?? []).' lag');
        }

        return self::SUCCESS;
    }
}
