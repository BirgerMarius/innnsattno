<?php

namespace App\Http\Controllers;

use App\Services\EuropaLeagueService;

class EuropaLeagueController extends UefaCompetitionController
{
    public function __construct(EuropaLeagueService $competitionService)
    {
        parent::__construct($competitionService);
    }

    protected function competitionName(): string { return 'Europa League'; }
    protected function routePrefix(): string { return 'europa-league'; }
}
