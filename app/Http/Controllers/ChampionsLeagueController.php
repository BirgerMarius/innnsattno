<?php

namespace App\Http\Controllers;

use App\Services\ChampionsLeagueService;
use App\Services\TvGuideService;

class ChampionsLeagueController extends UefaCompetitionController
{
    public function __construct(ChampionsLeagueService $competitionService, TvGuideService $tvGuideService)
    {
        parent::__construct($competitionService, $tvGuideService);
    }

    protected function competitionName(): string { return 'Champions League'; }
    protected function routePrefix(): string { return 'champions-league'; }
}
