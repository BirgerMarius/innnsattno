<?php

namespace Tests\Unit;

use App\Services\EliteserienService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EliteserienServiceTest extends TestCase
{
    /** @test */
    public function it_uses_verified_eliteserien_config_defaults_and_endpoints(): void
    {
        $service = new EliteserienService();

        $this->assertSame(38, $service->tournamentId());
        $this->assertSame(8766, $service->seasonId());
        $this->assertStringEndsWith('/tournaments/seasons/8766', $service->endpoints()['season_details']);
        $this->assertStringEndsWith('/tournaments/seasons/8766/schedule', $service->endpoints()['schedule']);
        $this->assertStringEndsWith('/tournaments/seasons/8766/standings', $service->endpoints()['standings']);
        $this->assertStringEndsWith('/tournaments/38', $service->endpoints()['tournament_details']);
        $this->assertStringEndsWith('/tournaments/38/seasons', $service->endpoints()['tournament_seasons']);
    }

    /** @test */
    public function it_normalizes_eliteserien_standings_fixtures_results_and_norwegian_dates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-01 12:00:00', 'Europe/Oslo'));

        try {
            $data = (new EliteserienService())->normalizeCompetitionData(
                $this->schedulePayload(240, 16),
                $this->standingsPayload(16)
            );

            $this->assertCount(16, $data['standings']);
            $this->assertSame('Eliteserien Team 1', $data['standings'][0]['teamName']);
            $this->assertSame(1, $data['standings'][0]['goalDifference']);
            $this->assertCount(12, $data['upcomingFixtures']);
            $this->assertCount(12, $data['recentResults']);
            $this->assertSame('14.07.2026', $data['upcomingFixtures'][0]['dateLabel']);
            $this->assertSame('12:00', $data['upcomingFixtures'][0]['timeLabel']);
            $this->assertSame('Utsatt', $data['upcomingFixtures'][0]['statusLabel']);
            $this->assertNull($data['upcomingFixtures'][0]['homeScore']);
            $this->assertNull($data['recentResults'][0]['homeScore']);
            $this->assertSame('14.07.2026 12:00', $data['lastUpdated']->format('d.m.Y H:i'));
        } finally {
            Carbon::setTestNow();
        }
    }

    /** @test */
    public function it_extracts_eliteserien_club_logos_from_the_sportsnext_participant_images_shape(): void
    {
        $participants = [
            '23358' => ['id' => '23358', 'name' => 'Bodø/Glimt', 'images' => ['clubLogo' => ['id' => '89/8976c421-68fa-4366-b7b1-cf2828d5187f', 'source' => 'imgproxy', 'url' => 'https://cdn.sportsnext.schibsted.io/api/v1/sports-pro/images/89/8976c421-68fa-4366-b7b1-cf2828d5187f']]],
            '23344' => ['id' => '23344', 'name' => 'Start', 'images' => ['clubLogo' => ['id' => '21/21edd21f-1a6d-4729-9803-85cebd4acf95', 'source' => 'imgproxy', 'url' => 'https://cdn.sportsnext.schibsted.io/api/v1/sports-pro/images/21/21edd21f-1a6d-4729-9803-85cebd4acf95']]],
        ];

        $data = (new EliteserienService())->normalizeCompetitionData(
            ['participants' => $participants, 'events' => [['participantIds' => ['23358', '23344'], 'status' => ['type' => 'notStarted']]]],
            ['participants' => $participants, 'standings' => [['teamStandings' => [['teamId' => '23358', 'rank' => 1], ['teamId' => '23344', 'rank' => 2]]]]]
        );

        $this->assertSame('https://cdn.sportsnext.schibsted.io/api/v1/sports-pro/images/89/8976c421-68fa-4366-b7b1-cf2828d5187f?rule=clip-64x64', $data['standings'][0]['emblemUrl']);
        $this->assertSame('https://cdn.sportsnext.schibsted.io/api/v1/sports-pro/images/21/21edd21f-1a6d-4729-9803-85cebd4acf95?rule=clip-64x64', $data['matches'][0]['awayEmblemUrl']);
    }

    /** @test */
    public function it_handles_empty_api_responses_without_throwing_to_the_controller(): void
    {
        Cache::flush();

        Http::fake([
            '*/tournaments/seasons/8766/schedule' => Http::response([], 200),
            '*/tournaments/seasons/8766/standings' => Http::response([], 200),
        ]);

        $data = (new EliteserienService())->getCompetitionData();

        $this->assertTrue($data['apiConfigured']);
        $this->assertFalse($data['apiError']);
        $this->assertFalse($data['usingStaleData']);
        $this->assertSame([], $data['standings']);
        $this->assertSame([], $data['upcomingFixtures']);
        $this->assertSame([], $data['recentResults']);
    }

    /** @test */
    public function it_fetches_schedule_and_standings_for_verified_eliteserien_season_id(): void
    {
        Cache::flush();

        Http::fake([
            '*/tournaments/seasons/8766/schedule' => Http::response($this->schedulePayload(240, 16), 200),
            '*/tournaments/seasons/8766/standings' => Http::response($this->standingsPayload(16), 200),
        ]);

        $data = (new EliteserienService())->getCompetitionData();

        $this->assertTrue($data['apiConfigured']);
        $this->assertFalse($data['apiError']);
        $this->assertCount(16, $data['standings']);
        $this->assertCount(12, $data['upcomingFixtures']);
        $this->assertCount(12, $data['recentResults']);

        Http::assertSent(function ($request) {
            return $request->url() === config('services.schibsted_sports.base_url').'/tournaments/seasons/8766/schedule';
        });

        Http::assertSent(function ($request) {
            return $request->url() === config('services.schibsted_sports.base_url').'/tournaments/seasons/8766/standings';
        });
    }

    /** @test */
    public function it_falls_back_to_last_good_cache_when_the_api_fails(): void
    {
        Cache::flush();

        $scheduleCalls = 0;
        $standingsCalls = 0;

        Http::fake([
            '*/tournaments/seasons/8766/schedule' => function () use (&$scheduleCalls) {
                $scheduleCalls++;

                return $scheduleCalls === 1
                    ? Http::response($this->schedulePayload(240, 16), 200)
                    : Http::response([], 500);
            },
            '*/tournaments/seasons/8766/standings' => function () use (&$standingsCalls) {
                $standingsCalls++;

                return $standingsCalls === 1
                    ? Http::response($this->standingsPayload(16), 200)
                    : Http::response([], 500);
            },
        ]);

        (new EliteserienService())->getCompetitionData();

        Cache::forget('schibsted_sports.eliteserien.8766.schedule');
        Cache::forget('schibsted_sports.eliteserien.8766.standings');

        $data = (new EliteserienService())->getCompetitionData();

        $this->assertTrue($data['apiError']);
        $this->assertTrue($data['usingStaleData']);
        $this->assertCount(16, $data['standings']);
        $this->assertSame(2, $scheduleCalls);
        $this->assertSame(1, $standingsCalls);
    }

    private function schedulePayload(int $eventCount, int $teamCount): array
    {
        $participants = [];

        for ($i = 1; $i <= $teamCount; $i++) {
            $participants[$i] = ['name' => 'Eliteserien Team '.$i, 'logoUrl' => 'https://example.test/team-'.$i.'.png'];
        }

        $events = [];

        for ($i = 1; $i <= $eventCount; $i++) {
            $homeId = (($i - 1) % $teamCount) + 1;
            $awayId = ($i % $teamCount) + 1;
            $finished = $i <= 120;

            $events[] = [
                'id' => $i,
                'startDate' => $finished
                    ? ($i === 120 ? '2026-06-15T10:00:00Z' : '2026-06-14T10:00:00Z')
                    : ($i === 121 ? '2026-07-14T10:00:00Z' : '2099-07-15T10:00:00Z'),
                'participantIds' => [$homeId, $awayId],
                'status' => ['type' => $finished ? 'finished' : ($i === 121 ? 'postponed' : 'notstarted')],
                'results' => $i === 120 ? [] : [
                    $homeId => ['runningScore' => 2],
                    $awayId => ['runningScore' => 1],
                ],
                'tournament' => ['stageName' => 'Runde '.min(30, $i)],
            ];
        }

        return [
            'updatedAt' => '2026-07-14T10:00:00Z',
            'events' => $events,
            'participants' => $participants,
            'tournament' => ['id' => 38, 'name' => 'Eliteserien'],
            'tournamentSeason' => ['id' => 8766, 'name' => 'Eliteserien 2026'],
            'countries' => [],
        ];
    }

    private function standingsPayload(int $teamCount): array
    {
        $participants = [];
        $teamStandings = [];

        for ($i = 1; $i <= $teamCount; $i++) {
            $participants[$i] = ['name' => 'Eliteserien Team '.$i];
            $teamStandings[] = [
                'teamId' => $i,
                'rank' => $i,
                'played' => 10,
                'wins' => 5,
                'draws' => 2,
                'losses' => 3,
                'goalsFor' => 12,
                'goalsAgainst' => 11,
                'points' => 17,
            ];
        }

        return [
            'standings' => [
                ['groupName' => 'Eliteserien', 'teamStandings' => $teamStandings],
            ],
            'tournament' => ['id' => 38, 'name' => 'Eliteserien'],
            'tournamentSeason' => ['id' => 8766, 'name' => 'Eliteserien 2026'],
            'participants' => $participants,
            'countries' => [],
        ];
    }
}
