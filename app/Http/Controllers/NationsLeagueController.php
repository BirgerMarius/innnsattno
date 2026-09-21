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
        $weekFixtures = $this->nationsLeague->matchesForCalendarWeek($competition['matches'] ?? []);

        return view('nations-league.index', array_merge($this->emptyViewData(), $competition, $this->pageData($competition), [
            'weekFixturesByDate' => collect($weekFixtures)->groupBy('dateLabel'),
            'recentResultsByDate' => collect($competition['recentResults'])->groupBy('dateLabel'),
            'tvMatches' => $this->tvMatches(0, 'nations-league-tv-screen'),
        ]));
    }

    public function team(int $teamId)
    {
        $teamSeason = $this->nationsLeague->getTeamPhaseData($teamId, 'Nations League', ['group']);
        abort_unless($teamSeason, 404);

        $results = array_values(array_filter($teamSeason['teamMatches'], fn (array $match) => $match['isFinished']));
        $fixtures = array_values(array_filter($teamSeason['teamMatches'], fn (array $match) => !$match['isFinished']));
        $group = $this->nationsLeague->groupForTeam($teamSeason['standingsGroups'], $teamId);

        return view('champions-league.team', array_merge($teamSeason, [
            'competitionName' => 'Nations League',
            'seasonLabel' => $teamSeason['seasonName'] ?? '2026/28',
            'competitionRoute' => 'nations-league.index',
            'teamPrintRoute' => 'nations-league.team.print',
            'teamGroupName' => $group['stageName'] ?? null,
            'teamPhaseLabel' => 'League- og gruppespill',
            'results' => $results,
            'fixtures' => $fixtures,
            'tvMatches' => $this->tvMatches(1, 'nations-league-team-tv-screen'),
        ]));
    }

    public function teamPrint(int $teamId)
    {
        $teamSeason = $this->nationsLeague->getTeamPhaseData($teamId, 'Nations League', ['group']);
        abort_unless($teamSeason, 404);

        return view('football.team-print', array_merge($teamSeason, [
            'competitionName' => 'Nations League',
            'seasonLabel' => $teamSeason['seasonName'] ?? '2026/28',
            'backRoute' => 'nations-league.index',
            'teamRoute' => 'nations-league.team',
            'tvMatches' => $this->tvMatches(1, 'nations-league-team-tv-print'),
        ]));
    }

    public function print()
    {
        $printData = $this->nationsLeague->getPrintData('Nations League');

        return view('nations-league.print', array_merge($this->emptyViewData(), $printData, $this->pageData($printData), [
            'tvMatches' => $this->tvMatches(0, 'nations-league-tv-print'),
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
        return ['standingsGroups' => [], 'weekFixturesByDate' => [], 'upcomingFixtures' => [], 'recentResults' => []];
    }
}
