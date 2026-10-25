<?php

namespace App\Http\Controllers;

use App\Services\EliteserienService;
use App\Services\TvGuideService;

class EliteserienController extends Controller
{
    private EliteserienService $eliteserienService;

    public function __construct(EliteserienService $eliteserienService, private TvGuideService $tvGuideService)
    {
        $this->eliteserienService = $eliteserienService;
    }

    public function index()
    {
        $competition = $this->eliteserienService->getCompetitionData();

        return view('eliteserien.index', [
            'prison' => $this->prison(),
            'standings' => $competition['standings'],
            'upcomingFixtures' => $competition['upcomingFixtures'],
            'upcomingFixturesByDate' => collect($competition['upcomingFixtures'])->groupBy('dateLabel'),
            'recentResults' => $competition['recentResults'],
            'recentResultsByDate' => collect($competition['recentResults'])->groupBy('dateLabel'),
            'lastUpdated' => $competition['lastUpdated'],
            'apiConfigured' => $competition['apiConfigured'],
            'apiError' => $competition['apiError'],
            'usingStaleData' => $competition['usingStaleData'],
            'tvMatches' => $this->tvMatches(3, 'eliteserien-tv-screen', $this->prison()),
        ]);
    }

    public function test()
    {
        $competition = $this->eliteserienService->getCompetitionData();

        return view('eliteserien.test', [
            'tournamentId' => $this->eliteserienService->tournamentId(),
            'seasonId' => $this->eliteserienService->seasonId(),
            'endpoints' => $this->eliteserienService->endpoints(),
            'apiConfigured' => $competition['apiConfigured'],
            'apiError' => $competition['apiError'],
            'usingStaleData' => $competition['usingStaleData'],
            'standingCount' => count($competition['standings']),
            'fixtureCount' => count($competition['upcomingFixtures']),
            'resultCount' => count($competition['recentResults']),
            'lastUpdated' => $competition['lastUpdated'],
        ]);
    }

    public function print()
    {
        return view('football.competition-print', array_merge(
            $this->eliteserienService->getPrintData('Eliteserien'),
            ['returnUrl' => '/'.$this->prison().'/eliteserien', 'tvMatches' => $this->tvMatches(3, 'eliteserien-tv-print', $this->prison())],
        ));
    }

    public function team(int $teamId)
    {
        $teamSeason = $this->eliteserienService->getTeamSeasonData($teamId, 'Eliteserien');
        abort_unless($teamSeason, 404);

        return view('football.team', array_merge($teamSeason, [
            'backUrl' => '/'.$this->prison().'/eliteserien', 'printUrl' => '/'.$this->prison().'/eliteserien/lag/'.$teamId.'/utskrift',
            'tvMatches' => $this->tvMatches(1, 'eliteserien-team-tv-screen', $this->prison()),
        ]));
    }

    public function teamPrint(int $teamId)
    {
        $teamSeason = $this->eliteserienService->getTeamSeasonData($teamId, 'Eliteserien');
        abort_unless($teamSeason, 404);

        return view('football.team-print', array_merge($teamSeason, [
            'backUrl' => '/'.$this->prison().'/eliteserien', 'teamUrl' => '/'.$this->prison().'/eliteserien/lag/'.$teamId,
            'tvMatches' => $this->tvMatches(1, 'eliteserien-team-tv-print', $this->prison()),
        ]));
    }

    private function prison(): string { return in_array(request()->route('prison'), ['ringerike', 'ilseng', 'kongsvinger'], true) ? request()->route('prison') : 'ringerike'; }
    private function tvMatches(int $limit, string $operation, string $prison = 'ringerike'): array
    {
        return $this->tvGuideService->getUpcomingCompetitionMatches(
            now('Europe/Oslo'), 'eliteserien', $operation, $limit, false, $prison,
        );
    }
}
