<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PremierLeagueControllerTest extends TestCase
{
    /** @test */
    public function test_page_exposes_verified_premier_league_configuration_and_counts(): void
    {
        Cache::flush();

        Http::fake([
            '*/tournaments/seasons/9186/schedule' => Http::response($this->schedulePayload(), 200),
            '*/tournaments/seasons/9186/standings' => Http::response($this->standingsPayload(), 200),
        ]);

        $response = $this->get('/premier-league/test');

        $response->assertOk();
        $response->assertViewHas('tournamentId', 3);
        $response->assertViewHas('seasonId', 9186);
        $response->assertViewHas('apiConfigured', true);
        $response->assertViewHas('standingCount', 20);
        $response->assertViewHas('fixtureCount', 12);
        $response->assertViewHas('resultCount', 12);
        $response->assertSee('Schibsted turnerings-ID');
        $response->assertSee('9186');
    }

    private function schedulePayload(): array
    {
        $participants = [];

        for ($i = 1; $i <= 20; $i++) {
            $participants[$i] = ['name' => 'Team '.$i];
        }

        $events = [];

        for ($i = 1; $i <= 380; $i++) {
            $homeId = (($i - 1) % 20) + 1;
            $awayId = ($i % 20) + 1;
            $finished = $i <= 190;

            $events[] = [
                'id' => $i,
                'startDate' => $finished ? '2026-05-01T12:00:00Z' : '2099-08-01T12:00:00Z',
                'participantIds' => [$homeId, $awayId],
                'status' => ['type' => $finished ? 'finished' : 'notstarted'],
                'results' => [
                    $homeId => ['runningScore' => 1],
                    $awayId => ['runningScore' => 0],
                ],
            ];
        }

        return [
            'events' => $events,
            'participants' => $participants,
            'tournament' => ['id' => 3, 'name' => 'Premier League'],
            'tournamentSeason' => ['id' => 9186, 'name' => '2026/27'],
            'countries' => [],
        ];
    }

    private function standingsPayload(): array
    {
        $participants = [];
        $teamStandings = [];

        for ($i = 1; $i <= 20; $i++) {
            $participants[$i] = ['name' => 'Team '.$i];
            $teamStandings[] = [
                'teamId' => $i,
                'rank' => $i,
                'played' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'goalsFor' => 0,
                'goalsAgainst' => 0,
                'points' => 0,
            ];
        }

        return [
            'standings' => [
                ['groupName' => 'Premier League', 'teamStandings' => $teamStandings],
            ],
            'tournament' => ['id' => 3, 'name' => 'Premier League'],
            'tournamentSeason' => ['id' => 9186, 'name' => '2026/27'],
            'participants' => $participants,
            'countries' => [],
        ];
    }
}
