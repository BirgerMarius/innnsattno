<?php

namespace App\Http\Controllers;

use App\Services\ChampionsLeagueService;

class ChampionsLeagueController extends UefaCompetitionController
{
    public function __construct(ChampionsLeagueService $competitionService)
    {
        parent::__construct($competitionService);
    }

    protected function competitionName(): string { return 'Champions League'; }
    protected function routePrefix(): string { return 'champions-league'; }
}
