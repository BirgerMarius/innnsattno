<?php

namespace App\Http\Controllers;

use App\Services\SchibstedCompetitionService;
use Illuminate\Support\Carbon;

abstract class UefaCompetitionController extends Controller
{
    public function __construct(protected SchibstedCompetitionService $competitionService)
    {
    }

    abstract protected function competitionName(): string;

    abstract protected function routePrefix(): string;

    public function index()
    {
        $competition = $this->competitionService->getCompetitionData();
        $matches = $this->matchesForDisplay($competition['matches']);
        $upcomingFixtures = array_values(array_filter($matches, fn (array $match) => !$match['isFinished']
            && (!$match['startsAt'] || $match['startsAt']->greaterThanOrEqualTo(now('Europe/Oslo')->startOfDay()))));
        $recentResults = array_values(array_filter($matches, fn (array $match) => $match['isFinished']));

        usort($upcomingFixtures, fn (array $a, array $b) => strcmp((string) $a['sortDate'], (string) $b['sortDate']));
        usort($recentResults, fn (array $a, array $b) => strcmp((string) $b['sortDate'], (string) $a['sortDate']));

        return view('champions-league.index', array_merge($this->viewData(), [
            'standings' => $competition['standings'],
            'standingsGroups' => $competition['standingsGroups'],
            'upcomingFixtures' => array_slice($upcomingFixtures, 0, 12),
            'upcomingFixturesByDate' => collect(array_slice($upcomingFixtures, 0, 12))->groupBy('dateLabel'),
            'recentResults' => array_slice($recentResults, 0, 12),
            'recentResultsByDate' => collect(array_slice($recentResults, 0, 12))->groupBy('dateLabel'),
            'lastUpdated' => $competition['lastUpdated'],
            'apiConfigured' => $competition['apiConfigured'],
            'apiError' => $competition['apiError'],
            'usingStaleData' => $competition['usingStaleData'],
            'showingLeaguePhase' => $this->leaguePhaseHasStarted($competition['matches']),
            'knockoutRounds' => $this->competitionService->buildKnockoutRounds($competition['matches']),
        ]));
    }

    public function print()
    {
        $printData = $this->competitionService->getPrintData($this->competitionName());

        return view('champions-league.print', array_merge($printData, $this->viewData(), [
            'returnUrl' => route($this->routePrefix().'.index'),
            'knockoutRounds' => $this->competitionService->buildKnockoutRounds($printData['matches']),
        ]));
    }

    public function team(int $teamId)
    {
        $teamSeason = $this->competitionService->getTeamPhaseData($teamId, $this->competitionName(), ['group']);
        abort_unless($teamSeason, 404);

        [$results, $fixtures] = $this->splitLeaguePhaseMatches($teamSeason['teamMatches']);

        return view('champions-league.team', array_merge($teamSeason, $this->viewData(), [
            'results' => $results,
            'fixtures' => $fixtures,
        ]));
    }

    public function teamPrint(int $teamId)
    {
        $teamSeason = $this->competitionService->getTeamPhaseData($teamId, $this->competitionName(), ['group']);
        abort_unless($teamSeason, 404);

        return view('football.team-print', array_merge($teamSeason, $this->viewData(), [
            'teamRoute' => $this->routePrefix().'.team',
        ]));
    }

    private function viewData(): array
    {
        return [
            'competitionName' => $this->competitionName(),
            'seasonLabel' => '2026/27',
            'competitionRoute' => $this->routePrefix().'.index',
            'printRoute' => $this->routePrefix().'.print',
            'teamRoute' => $this->routePrefix().'.team',
            'teamPrintRoute' => $this->routePrefix().'.team.print',
        ];
    }

    private function matchesForDisplay(array $matches): array
    {
        if (!$this->leaguePhaseHasStarted($matches)) {
            return $matches;
        }

        return array_values(array_filter($matches, fn (array $match) => $this->isLeaguePhase($match)));
    }

    private function leaguePhaseHasStarted(array $matches): bool
    {
        $now = now('Europe/Oslo');

        foreach ($matches as $match) {
            if ($this->isLeaguePhase($match) && ($match['isFinished'] || ($match['startsAt'] instanceof Carbon && $match['startsAt']->lessThanOrEqualTo($now)))) {
                return true;
            }
        }

        return false;
    }

    private function isLeaguePhase(array $match): bool
    {
        return ($match['phaseType'] ?? null) === 'group' || ($match['phase'] ?? null) === 'group';
    }

    private function splitLeaguePhaseMatches(array $matches): array
    {
        return [
            array_values(array_filter($matches, fn (array $match) => $match['isFinished'])),
            array_values(array_filter($matches, fn (array $match) => !$match['isFinished'])),
        ];
    }
}
