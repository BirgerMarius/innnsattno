<?php

namespace Tests\Unit;

use App\Services\Schibsted\SchibstedFootballDiscoveryService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SchibstedFootballDiscoveryServiceTest extends TestCase
{
    /** @test */
    public function it_parses_tournament_and_season_data(): void
    {
        $service = new SchibstedFootballDiscoveryService();

        $entry = $service->normalizeTournament([
            'id' => 123,
            'slug' => 'premier-league',
            'name' => 'Premier League',
            'country' => ['name' => 'England'],
            'gender' => 'men',
            'competitionType' => 'club',
            'currentSeason' => [
                'id' => 9876,
                'name' => '2026/27',
                'startDate' => '2026-08-15',
                'endDate' => '2027-05-24',
                'current' => true,
            ],
            'seasons' => [
                ['id' => 9876, 'name' => '2026/27', 'current' => true],
                ['id' => 8765, 'name' => '2025/26', 'current' => false],
            ],
        ], '2026-07-14T00:00:00Z');

        $this->assertSame('Premier League', $entry['name']);
        $this->assertSame('England', $entry['country']);
        $this->assertSame(123, $entry['tournament_id']);
        $this->assertSame('premier-league', $entry['tournament_slug']);
        $this->assertSame(9876, $entry['current_season_id']);
        $this->assertCount(2, $entry['available_seasons']);
    }

    /** @test */
    public function it_parses_seasons_from_flat_season_payload(): void
    {
        $service = new SchibstedFootballDiscoveryService();

        $seasons = $service->normalizeSeasons([
            'seasonId' => 7767,
            'seasonName' => '2026',
            'startDate' => '2026-06-11',
            'endDate' => '2026-07-19',
        ]);

        $this->assertSame(7767, $seasons[0]['season_id']);
        $this->assertSame('2026', $seasons[0]['name']);
        $this->assertSame('2026-06-11', $seasons[0]['start_date']);
    }

    /** @test */
    public function it_normalizes_capabilities_from_successful_endpoints(): void
    {
        $service = new SchibstedFootballDiscoveryService();

        $capabilities = $service->normalizeCapabilities([
            'schedule' => ['ok' => true],
            'standings' => ['ok' => true],
        ]);

        $this->assertTrue($capabilities['matches']);
        $this->assertTrue($capabilities['results']);
        $this->assertTrue($capabilities['upcoming_matches']);
        $this->assertTrue($capabilities['live_matches']);
        $this->assertTrue($capabilities['match_status']);
        $this->assertTrue($capabilities['standings']);
        $this->assertNull($capabilities['players']);
        $this->assertNull($capabilities['lineups']);
    }

    /** @test */
    public function it_handles_empty_api_responses(): void
    {
        Http::fake([
            '*' => Http::response([], 404),
        ]);

        $catalog = (new SchibstedFootballDiscoveryService())->discover('7767');

        $this->assertCount(1, $catalog['tournaments']);
        $this->assertSame(7767, $catalog['tournaments'][0]['current_season_id']);
        $this->assertNotEmpty($catalog['source']['network_notes']);
    }

    /** @test */
    public function it_handles_invalid_json_without_throwing(): void
    {
        Http::fake([
            '*schedule*' => Http::response('not-json', 200),
            '*' => Http::response([], 404),
        ]);

        $catalog = (new SchibstedFootballDiscoveryService())->discover('7767');

        $this->assertSame('invalid_json', $catalog['source']['network_notes'][0]['reason']);
    }

    /** @test */
    public function it_handles_connection_errors_without_throwing(): void
    {
        Http::fake(function () {
            throw new ConnectionException('DNS failed');
        });

        $catalog = (new SchibstedFootballDiscoveryService())->discover('7767');

        $this->assertSame('connection_error', $catalog['source']['network_notes'][0]['reason']);
    }

    /** @test */
    public function command_dry_run_does_not_write_catalog(): void
    {
        $path = sys_get_temp_dir().'/schibsted-football-dry-run-test.json';
        File::delete($path);

        Http::fake([
            '*schedule*' => Http::response($this->schedulePayload(), 200),
            '*standings*' => Http::response($this->standingsPayload(), 200),
            '*' => Http::response([], 404),
        ]);

        $this->artisan('football:schibsted-discover', [
            '--dry-run' => true,
            '--tournament' => '7767',
            '--output' => $path,
        ])->assertExitCode(0);

        $this->assertFileDoesNotExist($path);
    }

    /** @test */
    public function command_writes_catalog_when_not_dry_run(): void
    {
        $path = sys_get_temp_dir().'/schibsted-football-write-test.json';
        File::delete($path);

        Http::fake([
            '*schedule*' => Http::response($this->schedulePayload(), 200),
            '*standings*' => Http::response($this->standingsPayload(), 200),
            '*' => Http::response([], 404),
        ]);

        $this->artisan('football:schibsted-discover', [
            '--tournament' => '7767',
            '--output' => $path,
        ])->assertExitCode(0);

        $this->assertFileExists($path);

        $catalog = json_decode(File::get($path), true);

        $this->assertSame('FIFA World Cup 2026', $catalog['tournaments'][0]['name']);
        $this->assertSame(19, $catalog['tournaments'][0]['tournament_id']);
        $this->assertSame('confirmed', $catalog['tournaments'][0]['capabilities']['standings']);
        $this->assertSame('confirmed', $catalog['tournaments'][0]['endpoint_status']['schedule']);

        File::delete($path);
    }

    /** @test */
    public function catalog_marks_manual_world_cup_premier_league_and_eliteserien_verification(): void
    {
        Http::fake(['*' => Http::response([], 404)]);

        $catalog = (new SchibstedFootballDiscoveryService())->discover();

        $worldCup = collect($catalog['tournaments'])->firstWhere('name', 'FIFA World Cup 2026');
        $premierLeague = collect($catalog['tournaments'])->firstWhere('name', 'Premier League');
        $eliteserien = collect($catalog['tournaments'])->firstWhere('name', 'Eliteserien');

        $this->assertSame('live_verification', $worldCup['source']);
        $this->assertSame('confirmed', $worldCup['verification_status']);
        $this->assertSame('live_api_from_docker01', $worldCup['verification_method']);
        $this->assertSame('World Cup 2026', $worldCup['current_season_name']);
        $this->assertSame(7767, $worldCup['current_season_id']);
        $this->assertSame('/tournaments/seasons/7767/schedule', $worldCup['endpoints']['schedule']);
        $this->assertSame('live_verification', $premierLeague['source']);
        $this->assertSame('confirmed', $premierLeague['verification_status']);
        $this->assertSame('live_api_from_docker01', $premierLeague['verification_method']);
        $this->assertSame(3, $premierLeague['tournament_id']);
        $this->assertSame(9186, $premierLeague['current_season_id']);
        $this->assertSame('/tournaments/seasons/9186/schedule', $premierLeague['endpoints']['schedule']);
        $this->assertSame('live_verification', $eliteserien['source']);
        $this->assertSame('confirmed', $eliteserien['verification_status']);
        $this->assertSame('live_api_from_docker01', $eliteserien['verification_method']);
        $this->assertSame(38, $eliteserien['tournament_id']);
        $this->assertSame(8766, $eliteserien['current_season_id']);
        $this->assertSame('/tournaments/seasons/8766/schedule', $eliteserien['endpoints']['schedule']);
    }

    /** @test */
    public function catalog_preserves_verified_world_cup_endpoints_and_rejected_candidates(): void
    {
        Http::fake(['*' => Http::response([], 404)]);

        $catalog = (new SchibstedFootballDiscoveryService())->discover();
        $worldCup = collect($catalog['tournaments'])->firstWhere('name', 'FIFA World Cup 2026');

        $confirmed = [
            'schedule' => '/tournaments/seasons/7767/schedule',
            'standings' => '/tournaments/seasons/7767/standings',
            'season_details' => '/tournaments/seasons/7767',
            'tournament_details' => '/tournaments/19',
            'tournament_seasons' => '/tournaments/19/seasons',
        ];

        foreach ($confirmed as $key => $path) {
            $this->assertSame($path, $worldCup['endpoints'][$key]);
            $this->assertSame('confirmed', $worldCup['endpoint_status'][$key]);
            $this->assertSame('confirmed', $worldCup['capabilities'][$key]);
        }

        foreach ([
            'season_participants_endpoint',
            'season_teams_endpoint',
            'season_statistics_endpoint',
            'season_lineups_endpoint',
        ] as $key) {
            $this->assertSame('rejected_404', $worldCup['endpoint_status'][$key]);
            $this->assertSame('rejected_404', $worldCup['capabilities'][$key]);
        }
    }

    /** @test */
    public function discovery_does_not_degrade_live_verified_world_cup_premier_league_or_eliteserien_ids(): void
    {
        Http::fake(['*' => Http::response([], 404)]);

        $catalog = (new SchibstedFootballDiscoveryService())->discover();
        $worldCup = collect($catalog['tournaments'])->firstWhere('name', 'FIFA World Cup 2026');
        $premierLeague = collect($catalog['tournaments'])->firstWhere('name', 'Premier League');
        $eliteserien = collect($catalog['tournaments'])->firstWhere('name', 'Eliteserien');

        $this->assertSame('confirmed', $worldCup['verification_status']);
        $this->assertSame(19, $worldCup['tournament_id']);
        $this->assertSame(7767, $worldCup['current_season_id']);
        $this->assertSame('confirmed', $worldCup['endpoint_status']['schedule']);
        $this->assertSame('confirmed', $premierLeague['verification_status']);
        $this->assertSame(3, $premierLeague['tournament_id']);
        $this->assertSame(9186, $premierLeague['current_season_id']);
        $this->assertSame('confirmed', $premierLeague['endpoint_status']['schedule']);
        $this->assertSame('confirmed', $premierLeague['endpoint_status']['standings']);
        $this->assertSame(380, $premierLeague['observed_response']['schedule_event_count']);
        $this->assertSame(20, $premierLeague['observed_response']['standings_team_count']);
        $this->assertSame(34, $premierLeague['observed_response']['tournament_season_count']);
        $this->assertSame('confirmed', $eliteserien['verification_status']);
        $this->assertSame(38, $eliteserien['tournament_id']);
        $this->assertSame(8766, $eliteserien['current_season_id']);
        $this->assertSame('confirmed', $eliteserien['endpoint_status']['schedule']);
        $this->assertSame('confirmed', $eliteserien['endpoint_status']['standings']);
        $this->assertSame(240, $eliteserien['observed_response']['schedule_event_count']);
        $this->assertSame(16, $eliteserien['observed_response']['standings_team_count']);
        $this->assertSame(19, $eliteserien['observed_response']['tournament_season_count']);
    }

    /** @test */
    public function catalog_preserves_verified_premier_league_endpoints_and_observed_counts(): void
    {
        Http::fake(['*' => Http::response([], 404)]);

        $catalog = (new SchibstedFootballDiscoveryService())->discover();
        $premierLeague = collect($catalog['tournaments'])->firstWhere('name', 'Premier League');

        $confirmed = [
            'season_details' => '/tournaments/seasons/9186',
            'schedule' => '/tournaments/seasons/9186/schedule',
            'standings' => '/tournaments/seasons/9186/standings',
            'tournament_details' => '/tournaments/3',
            'tournament_seasons' => '/tournaments/3/seasons',
        ];

        foreach ($confirmed as $key => $path) {
            $this->assertSame($path, $premierLeague['endpoints'][$key]);
            $this->assertSame('confirmed', $premierLeague['endpoint_status'][$key]);
            $this->assertSame('confirmed', $premierLeague['capabilities'][$key]);
        }

        $this->assertTrue($premierLeague['capabilities']['matches']);
        $this->assertTrue($premierLeague['capabilities']['results']);
        $this->assertTrue($premierLeague['capabilities']['upcoming_matches']);
        $this->assertTrue($premierLeague['capabilities']['match_status']);
        $this->assertTrue($premierLeague['capabilities']['teams']);
        $this->assertSame(380, $premierLeague['observed_response']['schedule_event_count']);
        $this->assertSame(1, $premierLeague['observed_response']['standings_group_count']);
        $this->assertSame(20, $premierLeague['observed_response']['standings_team_count']);
        $this->assertSame(34, $premierLeague['observed_response']['tournament_season_count']);
        $this->assertSame(['schedule', 'standings'], $premierLeague['observed_response']['participants_embedded_in']);
        $this->assertSame(9186, $premierLeague['available_seasons'][0]['season_id']);
        $this->assertSame('2026/27', $premierLeague['available_seasons'][0]['name']);
    }

    /** @test */
    public function catalog_preserves_verified_eliteserien_endpoints_and_observed_counts(): void
    {
        Http::fake(['*' => Http::response([], 404)]);

        $catalog = (new SchibstedFootballDiscoveryService())->discover();
        $eliteserien = collect($catalog['tournaments'])->firstWhere('name', 'Eliteserien');

        $confirmed = [
            'tournament_details' => '/tournaments/38',
            'tournament_seasons' => '/tournaments/38/seasons',
            'season_details' => '/tournaments/seasons/8766',
            'schedule' => '/tournaments/seasons/8766/schedule',
            'standings' => '/tournaments/seasons/8766/standings',
        ];

        foreach ($confirmed as $key => $path) {
            $this->assertSame($path, $eliteserien['endpoints'][$key]);
            $this->assertSame('confirmed', $eliteserien['endpoint_status'][$key]);
            $this->assertSame('confirmed', $eliteserien['capabilities'][$key]);
        }

        $this->assertTrue($eliteserien['capabilities']['matches']);
        $this->assertTrue($eliteserien['capabilities']['results']);
        $this->assertTrue($eliteserien['capabilities']['upcoming_matches']);
        $this->assertTrue($eliteserien['capabilities']['match_status']);
        $this->assertTrue($eliteserien['capabilities']['teams']);
        $this->assertSame(240, $eliteserien['observed_response']['schedule_event_count']);
        $this->assertSame(1, $eliteserien['observed_response']['standings_group_count']);
        $this->assertSame(16, $eliteserien['observed_response']['standings_team_count']);
        $this->assertSame(19, $eliteserien['observed_response']['tournament_season_count']);
        $this->assertSame(['schedule', 'standings'], $eliteserien['observed_response']['participants_embedded_in']);
        $this->assertSame(8766, $eliteserien['available_seasons'][0]['season_id']);
        $this->assertSame('Eliteserien 2026', $eliteserien['available_seasons'][0]['name']);
    }

    private function schedulePayload(): array
    {
        return [
            'participants' => [
                1 => ['name' => 'Home FC', 'countryCode' => 'eng'],
                2 => ['name' => 'Away FC', 'countryCode' => 'nor'],
            ],
            'events' => [
                [
                    'id' => 55,
                    'startDate' => '2026-08-15T14:00:00Z',
                    'participantIds' => [1, 2],
                    'status' => ['type' => 'notstarted'],
                    'tournament' => [
                        'id' => 99,
                        'name' => 'Example Cup',
                        'phaseType' => 'cup',
                    ],
                ],
            ],
        ];
    }

    private function standingsPayload(): array
    {
        return [
            'participants' => [
                1 => ['name' => 'Home FC', 'countryCode' => 'eng'],
                2 => ['name' => 'Away FC', 'countryCode' => 'nor'],
            ],
            'standings' => [
                [
                    'groupName' => 'Group A',
                    'teamStandings' => [
                        ['teamId' => 1, 'rank' => 1, 'points' => 3],
                    ],
                ],
            ],
        ];
    }
}
