<?php

namespace App\Services;

class EuropaLeagueService extends SchibstedCompetitionService
{
    protected function tournamentConfigKey(): string { return 'services.schibsted_sports.europa_league_tournament_id'; }
    protected function seasonConfigKey(): string { return 'services.schibsted_sports.europa_league_season_id'; }
    protected function cachePrefix(): string { return 'europa_league'; }
    protected function competitionLogName(): string { return 'Europa League'; }
}
