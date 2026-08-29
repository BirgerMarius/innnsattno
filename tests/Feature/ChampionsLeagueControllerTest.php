<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChampionsLeagueControllerTest extends TestCase
{
    /** @test */
    public function champions_league_page_and_print_view_render_verified_data(): void
    {
        Cache::flush();
        Http::fake([
            '*/tournaments/seasons/9168/schedule' => Http::response($this->schedulePayload(), 200),
            '*/tournaments/seasons/9168/standings' => Http::response($this->standingsPayload(), 200),
        ]);

        $this->get('/champions-league')->assertOk()->assertSee('Champions League')->assertSee('Bodø/Glimt')->assertSee('Tabell / ligafase');
        $this->get('/champions-league/print')->assertOk()->assertSee('Ligafasetabell')->assertSee('@page');
    }

    private function schedulePayload(): array
    {
        return ['participants' => [23358 => ['name' => 'Bodø/Glimt', 'images' => ['clubLogo' => ['url' => 'https://example.test/bodo.png']]], 2 => ['name' => 'Motstander']], 'events' => [[
            'id' => 1, 'startDate' => '2026-09-10T19:00:00Z', 'participantIds' => [23358, 2], 'status' => ['type' => 'notStarted'],
            'tournament' => ['phaseType' => 'group', 'phase' => 'group', 'stageName' => 'UEFA Champions League 26/27', 'round' => '1'],
        ]]];
    }

    private function standingsPayload(): array
    {
        $rows = [];
        for ($id = 1; $id <= 36; $id++) {
            $rows[] = ['teamId' => $id === 1 ? 23358 : $id + 1, 'rank' => $id, 'played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'goalsFor' => 0, 'goalsAgainst' => 0, 'goalsDiff' => 0, 'points' => 0];
        }

        return ['participants' => [23358 => ['name' => 'Bodø/Glimt', 'images' => ['clubLogo' => ['url' => 'https://example.test/bodo.png']]]], 'standings' => [['stageName' => 'UEFA Champions League 26/27', 'teamStandings' => $rows]]];
    }
}
