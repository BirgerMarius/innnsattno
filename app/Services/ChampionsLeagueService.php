<?php

namespace App\Services;

class ChampionsLeagueService extends SchibstedCompetitionService
{
    protected function tournamentConfigKey(): string
    {
        return 'services.schibsted_sports.champions_league_tournament_id';
    }

    protected function seasonConfigKey(): string
    {
        return 'services.schibsted_sports.champions_league_season_id';
    }

    protected function cachePrefix(): string
    {
        return 'champions_league';
    }

    protected function competitionLogName(): string
    {
        return 'Champions League';
    }
}
