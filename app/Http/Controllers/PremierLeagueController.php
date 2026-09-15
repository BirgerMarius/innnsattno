<?php

namespace App\Http\Controllers;

use App\Services\PremierLeagueService;
use App\Services\TvGuideService;

class PremierLeagueController extends Controller
{
    private PremierLeagueService $premierLeagueService;

    public function __construct(
        PremierLeagueService $premierLeagueService,
        private TvGuideService $tvGuideService,
    )
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
        return view('football.competition-print', array_merge(
            $this->premierLeagueService->getPrintData('Premier League'),
            [
                'returnUrl' => route('premier-league.index'),
                'tv3Plus' => $this->tv3PlusMatches(3),
            ],
        ));
    }

    public function team(int $teamId)
    {
        $teamSeason = $this->premierLeagueService->getTeamSeasonData($teamId, 'Premier League');
        abort_unless($teamSeason, 404);

        return view('football.team', array_merge($teamSeason, [
            'backRoute' => 'premier-league.index',
            'printRoute' => 'premier-league.team.print',
        ]));
    }

    public function teamPrint(int $teamId)
    {
        $teamSeason = $this->premierLeagueService->getTeamSeasonData($teamId, 'Premier League');
        abort_unless($teamSeason, 404);

        return view('football.team-print', array_merge($teamSeason, [
            'backRoute' => 'premier-league.index',
            'teamRoute' => 'premier-league.team',
            'tv3Plus' => $this->tv3PlusMatches(1),
        ]));
    }

    private function tv3PlusMatches(int $limit): array
    {
        return $this->tvGuideService->getUpcomingPremierLeagueOnTv3Plus(
            now('Europe/Oslo'),
            'premier-league-tv3-plus-print',
            $limit,
        );
    }
}
