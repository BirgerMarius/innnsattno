<?php
namespace App\Http\Controllers;
use App\Services\BundesligaService;
use App\Services\TvGuideService;
class BundesligaController extends DomesticLeagueController {
    public function __construct(BundesligaService $service, TvGuideService $tv) { parent::__construct($service, $tv); }
    protected function competitionName(): string { return 'Bundesliga'; }
    protected function routePrefix(): string { return 'bundesliga'; }
    protected function seasonLabel(): string { return '2026/27'; }
}
