<?php

namespace App\Services;

class ObosLigaenService extends SchibstedCompetitionService
{
    protected function tournamentConfigKey(): string { return 'services.schibsted_sports.obos_ligaen_tournament_id'; }
    protected function seasonConfigKey(): string { return 'services.schibsted_sports.obos_ligaen_season_id'; }
    protected function cachePrefix(): string { return 'obos_ligaen'; }
    protected function competitionLogName(): string { return 'OBOS-ligaen'; }
}
