<?php
namespace App\Http\Controllers;
use App\Services\ObosLigaenService;
use App\Services\TvGuideService;
class ObosLigaenController extends DomesticLeagueController {
    public function __construct(ObosLigaenService $service, TvGuideService $tv) { parent::__construct($service, $tv); }
    protected function competitionName(): string { return 'OBOS-ligaen'; }
    protected function routePrefix(): string { return 'obos-ligaen'; }
    protected function seasonLabel(): string { return '2026'; }
}
