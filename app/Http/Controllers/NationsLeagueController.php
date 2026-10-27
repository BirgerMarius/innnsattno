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
            'prison' => $this->prison(),
            'weekFixturesByDate' => collect($weekFixtures)->groupBy('dateLabel'),
            'recentResultsByDate' => collect($competition['recentResults'])->groupBy('dateLabel'),
            // The TV box is a live schedule, unlike "Denne ukens kamper"
            // below, which deliberately remains a calendar-week overview.
            'tvMatches' => $this->withMatchEmblems(
                $this->tvMatches(0, 'nations-league-tv-screen', true, $this->prison()),
                $competition['matches'] ?? [],
                false,
            ),
        ]));
    }

    public function team(string|int $prisonOrTeamId, ?int $teamId = null)
    {
        $teamId ??= (int) $prisonOrTeamId;
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
            'competitionUrl' => '/'.$this->prison().'/nations-league',
            'teamPrintUrl' => '/'.$this->prison().'/nations-league/lag/'.$teamId.'/utskrift',
            'teamGroupName' => $group['stageName'] ?? null,
            'teamPhaseLabel' => 'League- og gruppespill',
            'results' => $results,
            'fixtures' => $fixtures,
            'tvMatches' => $this->tvMatches(1, 'nations-league-team-tv-screen', false, $this->prison()),
        ]));
    }

    public function teamPrint(string|int $prisonOrTeamId, ?int $teamId = null)
    {
        $teamId ??= (int) $prisonOrTeamId;
        $teamSeason = $this->nationsLeague->getTeamPhaseData($teamId, 'Nations League', ['group']);
        abort_unless($teamSeason, 404);

        return view('football.team-print', array_merge($teamSeason, [
            'competitionName' => 'Nations League',
            'seasonLabel' => $teamSeason['seasonName'] ?? '2026/28',
            'backUrl' => '/'.$this->prison().'/nations-league', 'teamUrl' => '/'.$this->prison().'/nations-league/lag/'.$teamId,
            'tvMatches' => $this->tvMatches(1, 'nations-league-team-tv-print', false, $this->prison()),
        ]));
    }

    public function print()
    {
        $printData = $this->nationsLeague->getPrintData('Nations League');
        $tvMatches = $this->withMatchEmblems(
            $this->tvMatches(0, 'nations-league-tv-print', true, $this->prison()),
            $printData['matches'] ?? [],
        );

        return view('nations-league.print', array_merge($this->emptyViewData(), $printData, $this->pageData($printData), [
            'tvMatches' => $tvMatches,
            'returnUrl' => '/'.$this->prison().'/nations-league',
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

    private function prison(): string { return app(\App\Services\PrisonContext::class)->prison()['slug']; }
    private function tvMatches(int $limit, string $operation, bool $useRollingSevenDayPeriod = false, string $prison = 'ringerike'): array
    {
        return $this->tvGuideService->getUpcomingCompetitionMatches(
            now('Europe/Oslo'), 'nations-league', $operation, $limit, $useRollingSevenDayPeriod, $prison,
        );
    }

    private function withMatchEmblems(array $tvMatches, array $matches, bool $includeTeamDetails = true): array
    {
        $matchEmblems = [];

        foreach ($matches as $match) {
            $matchEmblems[$this->fixtureKey($match['homeTeam'] ?? '', $match['awayTeam'] ?? '')] = $match;
        }

        foreach ($tvMatches['matches'] as &$tvMatch) {
            $fixture = $matchEmblems[$this->fixtureKeyFromTitle($tvMatch['name'] ?? '')] ?? null;

            if ($fixture) {
                $tvMatch = array_merge($tvMatch, [
                    'homeTeamId' => $fixture['homeTeamId'],
                    'awayTeamId' => $fixture['awayTeamId'],
                ]);

                if ($includeTeamDetails) {
                    $tvMatch = array_merge($tvMatch, [
                        'homeTeam' => $fixture['homeTeam'],
                        'awayTeam' => $fixture['awayTeam'],
                        'homeEmblemUrl' => $fixture['homeEmblemUrl'],
                        'awayEmblemUrl' => $fixture['awayEmblemUrl'],
                    ]);
                }
            }
        }
        unset($tvMatch);

        return $tvMatches;
    }

    private function fixtureKeyFromTitle(string $title): string
    {
        $teams = preg_split('/\s*[–-]\s*/u', $title, 2);

        return count($teams) === 2 ? $this->fixtureKey($teams[0], $teams[1]) : '';
    }

    private function fixtureKey(string $homeTeam, string $awayTeam): string
    {
        return mb_strtolower(trim($homeTeam).'|'.trim($awayTeam));
    }

    private function emptyViewData(): array
    {
        return ['standingsGroups' => [], 'weekFixturesByDate' => [], 'upcomingFixtures' => [], 'recentResults' => []];
    }
}
