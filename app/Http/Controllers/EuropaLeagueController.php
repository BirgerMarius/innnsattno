<?php

namespace App\Http\Controllers;

use App\Services\EuropaLeagueService;
use App\Services\TvGuideService;

class EuropaLeagueController extends UefaCompetitionController
{
    public function __construct(EuropaLeagueService $competitionService, TvGuideService $tvGuideService)
    {
        parent::__construct($competitionService, $tvGuideService);
    }

    protected function competitionName(): string { return 'Europa League'; }
    protected function routePrefix(): string { return 'europa-league'; }
}
