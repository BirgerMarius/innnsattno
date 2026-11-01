<?php

namespace App\Services;

class BundesligaService extends SchibstedCompetitionService
{
    protected function tournamentConfigKey(): string { return 'services.schibsted_sports.bundesliga_tournament_id'; }
    protected function seasonConfigKey(): string { return 'services.schibsted_sports.bundesliga_season_id'; }
    protected function cachePrefix(): string { return 'bundesliga'; }
    protected function competitionLogName(): string { return 'Bundesliga'; }
}
