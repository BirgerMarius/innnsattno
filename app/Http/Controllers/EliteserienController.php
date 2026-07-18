<?php

namespace App\Http\Controllers;

use App\Services\EliteserienService;

class EliteserienController extends Controller
{
    private EliteserienService $eliteserienService;

    public function __construct(EliteserienService $eliteserienService)
    {
        $this->eliteserienService = $eliteserienService;
    }

    public function index()
    {
        $competition = $this->eliteserienService->getCompetitionData();

        return view('eliteserien.index', [
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
        return view('football.competition-print', $this->eliteserienService->getPrintData('Eliteserien'));
    }
}
