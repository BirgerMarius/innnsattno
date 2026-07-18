<?php

namespace App\Http\Controllers;

use App\Services\PremierLeagueService;

class PremierLeagueController extends Controller
{
    private PremierLeagueService $premierLeagueService;

    public function __construct(PremierLeagueService $premierLeagueService)
    {
        $this->premierLeagueService = $premierLeagueService;
    }

    public function index()
    {
        $competition = $this->premierLeagueService->getCompetitionData();

        return view('premier-league.index', [
            'standings' => $competition['standings'],
            'upcomingFixtures' => $competition['upcomingFixtures'],
            'upcomingFixturesByDate' => collect($competition['upcomingFixtures'])->groupBy('dateLabel'),
            'recentResults' => $competition['recentResults'],
            'recentResultsByDate' => collect($competition['recentResults'])->groupBy('dateLabel'),
            'lastUpdated' => $competition['lastUpdated'],
            'apiConfigured' => $competition['apiConfigured'],
            'apiError' => $competition['apiError'],
            'usingStaleData' => $competition['usingStaleData'],
        ]);
    }

    public function test()
    {
        $competition = $this->premierLeagueService->getCompetitionData();

        return view('premier-league.test', [
            'tournamentId' => $this->premierLeagueService->tournamentId(),
            'seasonId' => $this->premierLeagueService->seasonId(),
            'endpoints' => $this->premierLeagueService->endpoints(),
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
        return view('football.competition-print', $this->premierLeagueService->getPrintData('Premier League'));
    }
}
