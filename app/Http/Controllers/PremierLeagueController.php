<?php

namespace App\Http\Controllers;

use App\Services\FootballApi\FootballApiException;
use App\Services\FootballApi\FootballService;

class PremierLeagueController extends Controller
{
    private FootballService $footballService;

    public function __construct(FootballService $footballService)
    {
        $this->footballService = $footballService;
    }

    public function index()
    {
        return view('premier-league.index');
    }

    public function test()
    {
        try {
            $standings = $this->footballService->premierLeagueStandings();
            $fixtures = $this->footballService->premierLeagueFixtures();
            $results = $this->footballService->premierLeagueResults();

            $teams = $this->teamsFrom($standings, $fixtures, $results);

            return view('premier-league.test', [
                'reachable' => true,
                'competitionCount' => $this->competitionCount($standings, $fixtures, $results),
                'teamCount' => count($teams),
                'standingCount' => $this->standingCount($standings),
                'fixtureCount' => count($fixtures['response'] ?? []),
                'resultCount' => count($results['response'] ?? []),
                'season' => $this->footballService->season(),
                'error' => null,
            ]);
        } catch (FootballApiException $exception) {
            return view('premier-league.test', [
                'reachable' => false,
                'competitionCount' => 0,
                'teamCount' => 0,
                'standingCount' => 0,
                'fixtureCount' => 0,
                'resultCount' => 0,
                'season' => $this->footballService->season(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function competitionCount(array ...$responses): int
    {
        foreach ($responses as $response) {
            if (!empty($response['response'])) {
                return 1;
            }
        }

        return 0;
    }

    private function standingCount(array $standings): int
    {
        $groups = $standings['response'][0]['league']['standings'] ?? [];

        return collect($groups)->flatten(1)->count();
    }

    private function teamsFrom(array $standings, array $fixtures, array $results): array
    {
        $teams = [];

        foreach (($standings['response'][0]['league']['standings'] ?? []) as $group) {
            foreach ($group as $standing) {
                $teamId = $standing['team']['id'] ?? null;

                if ($teamId) {
                    $teams[$teamId] = true;
                }
            }
        }

        foreach (array_merge($fixtures['response'] ?? [], $results['response'] ?? []) as $fixture) {
            foreach (['home', 'away'] as $side) {
                $teamId = $fixture['teams'][$side]['id'] ?? null;

                if ($teamId) {
                    $teams[$teamId] = true;
                }
            }
        }

        return $teams;
    }
}
