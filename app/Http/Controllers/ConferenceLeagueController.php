<?php

namespace App\Http\Controllers;

use App\Services\ConferenceLeagueService;

class ConferenceLeagueController extends UefaCompetitionController
{
    public function __construct(ConferenceLeagueService $competitionService)
    {
        parent::__construct($competitionService);
    }

    protected function competitionName(): string { return 'Conference League'; }
    protected function routePrefix(): string { return 'conference-league'; }
}
