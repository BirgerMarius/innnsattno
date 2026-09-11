<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FootballControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_football_pages_render_when_sportsnext_is_unavailable(): void
    {
        Http::fake(['api.sportsnext.schibsted.io/*' => Http::response([], 500)]);

        $this->get('/football')->assertOk()->assertSee('Fotball-VM 2026');
        $this->get('/fotball-utskrift')->assertOk()->assertSee('Fotball-VM 2026');
    }

    public function test_world_cup_print_omits_the_cup_section_until_cup_matches_are_published(): void
    {
        Http::fake([
            '*schedule' => Http::response($this->worldCupSchedule()),
            '*standings' => Http::response($this->worldCupStandings()),
        ]);

        $this->get('/fotball-utskrift')->assertOk()
            ->assertDontSee('<div class="playoff-columns">', false)
            ->assertDontSee('32-delsfinaler');
    }

    public function test_world_cup_print_shows_the_cup_section_when_cup_matches_are_published(): void
    {
        $schedule = $this->worldCupSchedule();
        $schedule['events'][] = [
            'startDate' => '2026-07-01T18:00:00Z',
            'participantIds' => [1, 2],
            'status' => ['type' => 'scheduled'],
            'results' => [],
            'tournament' => ['phaseType' => 'cup', 'stage' => 'roundOf32', 'stageName' => '32-delsfinale'],
            'winners' => [],
        ];
        Http::fake([
            '*schedule' => Http::response($schedule),
            '*standings' => Http::response($this->worldCupStandings()),
        ]);

        $this->get('/fotball-utskrift')->assertOk()
            ->assertSee('<div class="playoff-columns">', false)
            ->assertSee('32-delsfinaler');
    }

    private function worldCupSchedule(): array
    {
        return [
            'participants' => [
                1 => ['name' => 'Norge', 'countryCode' => 'nor'],
                2 => ['name' => 'Sverige', 'countryCode' => 'swe'],
            ],
            'events' => [[
                'startDate' => '2026-06-11T18:00:00Z',
                'participantIds' => [1, 2],
                'status' => ['type' => 'scheduled'],
                'results' => [],
                'tournament' => ['groupName' => 'A', 'phaseType' => 'group', 'stage' => 'groupStage', 'stageName' => 'Gruppe A'],
                'winners' => [],
            ]],
        ];
    }

    private function worldCupStandings(): array
    {
        return [
            'participants' => [1 => ['name' => 'Norge', 'countryCode' => 'nor']],
            'standings' => [[
                'groupName' => 'A',
                'teamStandings' => [[
                    'teamId' => 1, 'rank' => 1, 'played' => 0, 'wins' => 0, 'draws' => 0,
                    'losses' => 0, 'goalsFor' => 0, 'goalsAgainst' => 0, 'points' => 0,
                ]],
            ]],
        ];
    }
}
