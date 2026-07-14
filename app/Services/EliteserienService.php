<?php

namespace App\Services;

class EliteserienService extends SchibstedCompetitionService
{
    protected function tournamentConfigKey(): string
    {
        return 'services.schibsted_sports.eliteserien_tournament_id';
    }

    protected function seasonConfigKey(): string
    {
        return 'services.schibsted_sports.eliteserien_season_id';
    }

    protected function cachePrefix(): string
    {
        return 'eliteserien';
    }

    protected function competitionLogName(): string
    {
        return 'Eliteserien';
    }
}
