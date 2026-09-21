<?php

namespace App\Http\Controllers;

use App\Services\NationsLeagueService;
use App\Services\TvGuideService;

class NationsLeagueController extends Controller
{
    public function __construct(private NationsLeagueService $nationsLeague, private TvGuideService $tvGuideService)
    {
    }

    public function index()
    {
        $competition = $this->nationsLeague->getCompetitionData();

        return view('nations-league.index', array_merge($this->emptyViewData(), $competition, $this->pageData($competition), [
            'upcomingFixturesByDate' => collect($competition['upcomingFixtures'])->groupBy('dateLabel'),
            'recentResultsByDate' => collect($competition['recentResults'])->groupBy('dateLabel'),
            'tvMatches' => $this->tvMatches(3, 'nations-league-tv-screen'),
        ]));
    }

    public function print()
    {
        $printData = $this->nationsLeague->getPrintData('Nations League');

        return view('nations-league.print', array_merge($this->emptyViewData(), $printData, $this->pageData($printData), [
            'tvMatches' => $this->tvMatches(3, 'nations-league-tv-print'),
            'returnUrl' => route('nations-league.index'),
        ]));
    }

    private function pageData(array $competition): array
    {
        return [
            'competitionName' => 'UEFA Nations League',
            'seasonLabel' => $competition['seasonName'] ?? '2026/28',
            'norwayTeamId' => NationsLeagueService::NORWAY_TEAM_ID,
            'norwayGroup' => $this->nationsLeague->norwayGroup($competition['standingsGroups'] ?? []),
        ];
    }

    private function tvMatches(int $limit, string $operation): array
    {
        return $this->tvGuideService->getUpcomingCompetitionMatches(
            now('Europe/Oslo'), 'nations-league', $operation, $limit,
        );
    }

    private function emptyViewData(): array
    {
        return ['standingsGroups' => [], 'upcomingFixtures' => [], 'recentResults' => []];
    }
}
