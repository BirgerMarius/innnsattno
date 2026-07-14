<?php

namespace Tests\Unit;

use App\Services\PremierLeagueService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PremierLeagueServiceTest extends TestCase
{
    /** @test */
    public function it_normalizes_schibsted_standings_fixtures_and_results(): void
    {
        $service = new PremierLeagueService();

        $schedule = [
            'updatedAt' => '2026-07-14T10:00:00Z',
            'participants' => [
                1 => ['name' => 'Arsenal', 'logoUrl' => 'https://example.test/arsenal.png'],
                2 => ['name' => 'Coventry City'],
                3 => ['name' => 'Liverpool'],
                4 => ['name' => 'Bournemouth'],
            ],
            'events' => [
                [
                    'id' => 10,
                    'startDate' => '2099-08-22T14:00:00Z',
                    'participantIds' => [1, 2],
                    'status' => ['type' => 'notstarted'],
                    'tournament' => ['stageName' => 'Runde 1'],
                ],
                [
                    'id' => 11,
                    'startDate' => '2026-05-30T14:00:00Z',
                    'participantIds' => [3, 4],
                    'status' => ['type' => 'finished'],
                    'results' => [
                        3 => ['runningScore' => 2],
                        4 => ['runningScore' => 1],
                    ],
                    'tournament' => ['stageName' => 'Runde 38'],
                ],
            ],
        ];

        $standings = [
            'participants' => $schedule['participants'],
            'standings' => [
                [
                    'groupName' => 'Premier League',
                    'teamStandings' => [
                        [
                            'rank' => 1,
                            'teamId' => 1,
                            'played' => 1,
                            'wins' => 1,
                            'draws' => 0,
                            'losses' => 0,
                            'goalsFor' => 3,
                            'goalsAgainst' => 1,
                            'points' => 3,
                        ],
                    ],
                ],
            ],
        ];

        $data = $service->normalizeCompetitionData($schedule, $standings);

        $this->assertCount(1, $data['standings']);
        $this->assertSame('Arsenal', $data['standings'][0]['teamName']);
        $this->assertSame(2, $data['standings'][0]['goalDifference']);
        $this->assertSame('https://example.test/arsenal.png', $data['standings'][0]['emblemUrl']);
        $this->assertCount(1, $data['upcomingFixtures']);
        $this->assertSame('Arsenal', $data['upcomingFixtures'][0]['homeTeam']);
        $this->assertSame('Runde 1', $data['upcomingFixtures'][0]['round']);
        $this->assertCount(1, $data['recentResults']);
        $this->assertSame(2, $data['recentResults'][0]['homeScore']);
        $this->assertSame('14.07.2026 12:00', $data['lastUpdated']->format('d.m.Y H:i'));
    }

    /** @test */
    public function it_uses_verified_premier_league_config_defaults(): void
    {
        $service = new PremierLeagueService();

        $this->assertSame(3, $service->tournamentId());
        $this->assertSame(9186, $service->seasonId());
        $this->assertStringEndsWith('/tournaments/seasons/9186', $service->endpoints()['season_details']);
        $this->assertStringEndsWith('/tournaments/seasons/9186/schedule', $service->endpoints()['schedule']);
        $this->assertStringEndsWith('/tournaments/seasons/9186/standings', $service->endpoints()['standings']);
        $this->assertStringEndsWith('/tournaments/3', $service->endpoints()['tournament_details']);
        $this->assertStringEndsWith('/tournaments/3/seasons', $service->endpoints()['tournament_seasons']);
    }

    /** @test */
    public function it_fetches_schedule_and_standings_for_verified_season_id(): void
    {
        Cache::flush();

        $schedule = [
            'participants' => [
                1 => ['name' => 'Arsenal'],
                2 => ['name' => 'Liverpool'],
            ],
            'events' => [
                [
                    'id' => 100,
                    'startDate' => '2099-08-15T14:00:00Z',
                    'participantIds' => [1, 2],
                    'status' => ['type' => 'notstarted'],
                ],
            ],
        ];

        $standings = [
            'participants' => $schedule['participants'],
            'standings' => [
                [
                    'teamStandings' => [
                        ['teamId' => 1, 'rank' => 1, 'points' => 3],
                        ['teamId' => 2, 'rank' => 2, 'points' => 0],
                    ],
                ],
            ],
        ];

        Http::fake([
            '*/tournaments/seasons/9186/schedule' => Http::response($schedule, 200),
            '*/tournaments/seasons/9186/standings' => Http::response($standings, 200),
        ]);

        $data = (new PremierLeagueService())->getCompetitionData();

        $this->assertTrue($data['apiConfigured']);
        $this->assertFalse($data['apiError']);
        $this->assertCount(2, $data['standings']);
        $this->assertCount(1, $data['upcomingFixtures']);

        Http::assertSent(function ($request) {
            return $request->url() === config('services.schibsted_sports.base_url').'/tournaments/seasons/9186/schedule';
        });

        Http::assertSent(function ($request) {
            return $request->url() === config('services.schibsted_sports.base_url').'/tournaments/seasons/9186/standings';
        });
    }
}
