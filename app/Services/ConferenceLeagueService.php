<?php

namespace App\Services;

class ConferenceLeagueService extends SchibstedCompetitionService
{
    protected function tournamentConfigKey(): string { return 'services.schibsted_sports.conference_league_tournament_id'; }
    protected function seasonConfigKey(): string { return 'services.schibsted_sports.conference_league_season_id'; }
    protected function cachePrefix(): string { return 'conference_league'; }
    protected function competitionLogName(): string { return 'Conference League'; }
}
