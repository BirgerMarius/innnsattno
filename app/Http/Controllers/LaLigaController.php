<?php
namespace App\Http\Controllers;
use App\Services\LaLigaService;
use App\Services\TvGuideService;
class LaLigaController extends DomesticLeagueController {
    public function __construct(LaLigaService $service, TvGuideService $tv) { parent::__construct($service, $tv); }
    protected function competitionName(): string { return 'La Liga'; }
    protected function routePrefix(): string { return 'la-liga'; }
    protected function seasonLabel(): string { return '2026/27'; }
}
