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
use Illuminate\Console\Command;

class PrecacheFootballTeams extends Command
{
    protected $signature = 'football:precache-teams';

    protected $description = 'Oppdater SportsNext-lagdata og logoer som brukes av Fotball på TV.';

    public function handle(
        PremierLeagueService $premierLeague,
        EliteserienService $eliteserien,
        ObosLigaenService $obosLigaen,
        LaLigaService $laLiga,
        BundesligaService $bundesliga,
        ChampionsLeagueService $championsLeague,
        EuropaLeagueService $europaLeague,
        ConferenceLeagueService $conferenceLeague,
        NationsLeagueService $nationsLeague,
    ): int {
        foreach ([$premierLeague, $eliteserien, $obosLigaen, $laLiga, $bundesliga, $championsLeague, $europaLeague, $conferenceLeague, $nationsLeague] as $service) {
            $service->refreshCompetitionData();
            $data = $service->getCompetitionData();
            $this->line($service->competitionNameForCache().': '.count($data['teams'] ?? []).' lag');
        }

        return self::SUCCESS;
    }
}
