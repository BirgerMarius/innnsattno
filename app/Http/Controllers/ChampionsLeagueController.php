<?php

namespace App\Http\Controllers;

use App\Services\ChampionsLeagueService;
use Illuminate\Support\Carbon;

class ChampionsLeagueController extends Controller
{
    public function __construct(private ChampionsLeagueService $championsLeagueService)
    {
    }

    public function index()
    {
        $competition = $this->championsLeagueService->getCompetitionData();
        $matches = $this->matchesForDisplay($competition['matches']);
        $upcomingFixtures = array_values(array_filter($matches, function (array $match) {
            return !$match['isFinished']
                && (!$match['startsAt'] || $match['startsAt']->greaterThanOrEqualTo(now('Europe/Oslo')->startOfDay()));
        }));
        $recentResults = array_values(array_filter($matches, fn (array $match) => $match['isFinished']));

        usort($upcomingFixtures, fn (array $a, array $b) => strcmp((string) $a['sortDate'], (string) $b['sortDate']));
        usort($recentResults, fn (array $a, array $b) => strcmp((string) $b['sortDate'], (string) $a['sortDate']));

        $upcomingFixtures = array_slice($upcomingFixtures, 0, 12);
        $recentResults = array_slice($recentResults, 0, 12);

        return view('champions-league.index', [
            'standings' => $competition['standings'],
            'standingsGroups' => $competition['standingsGroups'],
            'upcomingFixtures' => $upcomingFixtures,
            'upcomingFixturesByDate' => collect($upcomingFixtures)->groupBy('dateLabel'),
            'recentResults' => $recentResults,
            'recentResultsByDate' => collect($recentResults)->groupBy('dateLabel'),
            'lastUpdated' => $competition['lastUpdated'],
            'apiConfigured' => $competition['apiConfigured'],
            'apiError' => $competition['apiError'],
            'usingStaleData' => $competition['usingStaleData'],
            'showingLeaguePhase' => $this->leaguePhaseHasStarted($competition['matches']),
            'knockoutRounds' => $this->championsLeagueService->buildKnockoutRounds($competition['matches']),
        ]);
    }

    public function print()
    {
        $printData = $this->championsLeagueService->getPrintData('Champions League');

        return view('champions-league.print', array_merge($printData, [
            'returnUrl' => route('champions-league.index'),
            'knockoutRounds' => $this->championsLeagueService->buildKnockoutRounds($printData['matches']),
        ]));
    }

    /**
     * Once a league-phase match has begun, keep qualifying matches out of the
     * main overview. Before then, qualification is the current competition.
     */
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
            if ($this->isLeaguePhase($match)
                && ($match['isFinished'] || ($match['startsAt'] instanceof Carbon && $match['startsAt']->lessThanOrEqualTo($now)))) {
                return true;
            }
        }

        return false;
    }

    private function isLeaguePhase(array $match): bool
    {
        return ($match['phaseType'] ?? null) === 'group' || ($match['phase'] ?? null) === 'group';
    }
}
