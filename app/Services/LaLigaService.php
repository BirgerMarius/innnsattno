<?php

namespace App\Services;

class LaLigaService extends SchibstedCompetitionService
{
    protected function tournamentConfigKey(): string { return 'services.schibsted_sports.la_liga_tournament_id'; }
    protected function seasonConfigKey(): string { return 'services.schibsted_sports.la_liga_season_id'; }
    protected function cachePrefix(): string { return 'la_liga'; }
    protected function competitionLogName(): string { return 'La Liga'; }
}
