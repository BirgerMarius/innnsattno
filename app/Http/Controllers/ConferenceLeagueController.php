<?php

namespace App\Http\Controllers;

use App\Services\ConferenceLeagueService;
use App\Services\TvGuideService;

class ConferenceLeagueController extends UefaCompetitionController
{
    public function __construct(ConferenceLeagueService $competitionService, TvGuideService $tvGuideService)
    {
        parent::__construct($competitionService, $tvGuideService);
    }

    protected function competitionName(): string { return 'Conference League'; }
    protected function routePrefix(): string { return 'conference-league'; }
}
