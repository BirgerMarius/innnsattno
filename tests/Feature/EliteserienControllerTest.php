<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EliteserienControllerTest extends TestCase
{
    /** @test */
    public function public_page_responds_with_eliteserien_data(): void
    {
        Cache::flush();

        Http::fake([
            '*/tournaments/seasons/8766/schedule' => Http::response($this->schedulePayload(), 200),
            '*/tournaments/seasons/8766/standings' => Http::response($this->standingsPayload(), 200),
        ]);

        $response = $this->get('/eliteserien');

        $response->assertOk();
        $response->assertSee('Eliteserien 2026');
        $response->assertSee('Tabell');
        $response->assertSee('Kommende kamper');
        $response->assertSee('Siste resultater');
        $response->assertSee('Kilde: Schibsted/VG SportsNext-data.');
    }

    /** @test */
    public function test_page_exposes_verified_eliteserien_configuration_and_counts(): void
    {
        Cache::flush();

        Http::fake([
            '*/tournaments/seasons/8766/schedule' => Http::response($this->schedulePayload(), 200),
            '*/tournaments/seasons/8766/standings' => Http::response($this->standingsPayload(), 200),
        ]);

        $response = $this->get('/eliteserien/test');

        $response->assertOk();
        $response->assertViewHas('tournamentId', 38);
        $response->assertViewHas('seasonId', 8766);
        $response->assertViewHas('apiConfigured', true);
        $response->assertViewHas('standingCount', 16);
        $response->assertViewHas('fixtureCount', 12);
        $response->assertViewHas('resultCount', 12);
        $response->assertSee('Schibsted turnerings-ID');
        $response->assertSee('8766');
        $response->assertSee('/tournaments/seasons/8766/schedule');
    }

    /** @test */
    public function test_page_reports_api_failure_without_exposing_secrets(): void
    {
        Cache::flush();

        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $response = $this->get('/eliteserien/test');

        $response->assertOk();
        $response->assertViewHas('apiError', true);
        $response->assertSee('API-feil ved siste forsøk');
        $response->assertDontSee('SCHIBSTED_SPORTS_BASE_URL');
        $response->assertDontSee('SCHIBSTED_ELITESERIEN_SEASON_ID');
    }

    /** @test */
    public function front_page_contains_eliteserien_button(): void
    {
        $response = $this->get('/tv');

        $response->assertOk();
        $response->assertSee('Eliteserien 2026');
        $response->assertSee('/eliteserien');
    }

    /** @test */
    public function catalog_file_contains_verified_eliteserien_counts(): void
    {
        $catalog = json_decode(File::get(storage_path('app/reference/schibsted-football-tournaments.json')), true);
        $eliteserien = collect($catalog['tournaments'])->firstWhere('name', 'Eliteserien');

        $this->assertSame(38, $eliteserien['tournament_id']);
        $this->assertSame(8766, $eliteserien['current_season_id']);
        $this->assertSame(240, $eliteserien['observed_response']['schedule_event_count']);
        $this->assertSame(16, $eliteserien['observed_response']['standings_team_count']);
        $this->assertSame(19, $eliteserien['observed_response']['tournament_season_count']);
    }

    private function schedulePayload(): array
    {
        $participants = [];

        for ($i = 1; $i <= 16; $i++) {
            $participants[$i] = ['name' => 'Eliteserien Team '.$i];
        }

        $events = [];

        for ($i = 1; $i <= 240; $i++) {
            $homeId = (($i - 1) % 16) + 1;
            $awayId = ($i % 16) + 1;
            $finished = $i <= 120;

            $events[] = [
                'id' => $i,
                'startDate' => $finished ? '2026-06-01T12:00:00Z' : '2099-08-01T12:00:00Z',
                'participantIds' => [$homeId, $awayId],
                'status' => ['type' => $finished ? 'finished' : 'notstarted'],
                'results' => [
                    $homeId => ['runningScore' => 1],
                    $awayId => ['runningScore' => 0],
                ],
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

    private function standingsPayload(): array
    {
        $participants = [];
        $teamStandings = [];

        for ($i = 1; $i <= 16; $i++) {
            $participants[$i] = ['name' => 'Eliteserien Team '.$i];
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
                ['groupName' => 'Eliteserien', 'teamStandings' => $teamStandings],
            ],
            'tournament' => ['id' => 38, 'name' => 'Eliteserien'],
            'tournamentSeason' => ['id' => 8766, 'name' => 'Eliteserien 2026'],
            'participants' => $participants,
            'countries' => [],
        ];
    }
}
