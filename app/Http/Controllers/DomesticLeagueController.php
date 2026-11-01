<?php

namespace App\Http\Controllers;

use App\Services\SchibstedCompetitionService;
use App\Services\TvGuideService;

abstract class DomesticLeagueController extends Controller
{
    public function __construct(protected SchibstedCompetitionService $competitionService, private TvGuideService $tvGuideService) {}

    abstract protected function competitionName(): string;
    abstract protected function routePrefix(): string;
    abstract protected function seasonLabel(): string;

    public function index()
    {
        $competition = $this->competitionService->getCompetitionData();

        return view('football.domestic-league', [
            'prison' => $this->prison(), 'leagueName' => $this->competitionName(), 'routePrefix' => $this->routePrefix(),
            'seasonLabel' => $this->seasonLabel(), 'standings' => $competition['standings'],
            'standingsGroups' => $competition['standingsGroups'] ?? [],
            'upcomingFixtures' => $competition['upcomingFixtures'],
            'upcomingFixturesByDate' => collect($competition['upcomingFixtures'])->groupBy('dateLabel'),
            'recentResults' => $competition['recentResults'],
            'recentResultsByDate' => collect($competition['recentResults'])->groupBy('dateLabel'),
            'lastUpdated' => $competition['lastUpdated'], 'apiConfigured' => $competition['apiConfigured'],
            'apiError' => $competition['apiError'], 'usingStaleData' => $competition['usingStaleData'],
            'tvMatches' => $this->tvMatches(3, $this->routePrefix().'-tv-screen'),
        ]);
    }

    public function print()
    {
        return view('football.competition-print', array_merge($this->competitionService->getPrintData($this->competitionName()), [
            'returnUrl' => '/'.$this->prison().'/'.$this->routePrefix(),
            'tvMatches' => $this->tvMatches(3, $this->routePrefix().'-tv-print'),
        ]));
    }

    public function team(string|int $prisonOrTeamId, ?int $teamId = null)
    {
        $teamId ??= (int) $prisonOrTeamId;
        $teamSeason = $this->competitionService->getTeamSeasonData($teamId, $this->competitionName());
        abort_unless($teamSeason, 404);
        return view('football.team', array_merge($teamSeason, [
            'backUrl' => '/'.$this->prison().'/'.$this->routePrefix(),
            'printUrl' => '/'.$this->prison().'/'.$this->routePrefix().'/lag/'.$teamId.'/utskrift',
            'tvMatches' => $this->tvMatches(1, $this->routePrefix().'-team-tv-screen'),
        ]));
    }

    public function teamPrint(string|int $prisonOrTeamId, ?int $teamId = null)
    {
        $teamId ??= (int) $prisonOrTeamId;
        $teamSeason = $this->competitionService->getTeamSeasonData($teamId, $this->competitionName());
        abort_unless($teamSeason, 404);
        return view('football.team-print', array_merge($teamSeason, [
            'backUrl' => '/'.$this->prison().'/'.$this->routePrefix(),
            'teamUrl' => '/'.$this->prison().'/'.$this->routePrefix().'/lag/'.$teamId,
            'tvMatches' => $this->tvMatches(1, $this->routePrefix().'-team-tv-print'),
        ]));
    }

    private function prison(): string { return app(\App\Services\PrisonContext::class)->prison()['slug']; }
    private function tvMatches(int $limit, string $operation): array { return $this->tvGuideService->getUpcomingCompetitionMatches(now('Europe/Oslo'), $this->routePrefix(), $operation, $limit, false, $this->prison()); }
}
