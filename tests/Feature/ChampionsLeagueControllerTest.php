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

    /** @test */
    public function champions_league_team_pages_only_show_league_phase_matches(): void
    {
        Cache::flush();
        Http::fake([
            '*/tournaments/seasons/9168/schedule' => Http::response($this->teamSchedulePayload(), 200),
            '*/tournaments/seasons/9168/standings' => Http::response($this->teamStandingsPayload(), 200),
        ]);

        $this->get('/champions-league')
            ->assertOk()
            ->assertSee('/champions-league/lag/23358', false)
            ->assertSee('https://example.test/bodo.png', false);

        $this->get('/champions-league/lag/23358')
            ->assertOk()
            ->assertSee('Bodø/Glimt')
            ->assertSee('Resultater')
            ->assertSee('Kommende kamper')
            ->assertSee('Ligafase-motstander')
            ->assertSee('Senere motstander')
            ->assertDontSee('Kvalifiseringsmotstander')
            ->assertSee('Runde 1')
            ->assertSee('Runde 2')
            ->assertSee('https://example.test/bodo.png', false);

        $this->get('/champions-league/lag/23358/print')
            ->assertOk()
            ->assertSee('Alle seriekamper')
            ->assertSee('Ligafase-motstander')
            ->assertDontSee('Kvalifiseringsmotstander');

        $this->get('/champions-league/lag/999999')->assertNotFound();

        $this->get('/champions-league/lag/4')->assertOk()
            ->assertSee('Dette laget har ingen Champions League-kamper i ligafasen i datagrunnlaget.');
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

    private function teamSchedulePayload(): array
    {
        return ['participants' => [
            23358 => ['name' => 'Bodø/Glimt', 'images' => ['clubLogo' => ['url' => 'https://example.test/bodo.png']]],
            2 => ['name' => 'Ligafase-motstander', 'images' => ['clubLogo' => ['url' => 'https://example.test/opponent.png']]],
            3 => ['name' => 'Senere motstander'],
            4 => ['name' => 'Kvalifiseringsmotstander'],
        ], 'events' => [
            ['id' => 1, 'startDate' => '2026-08-01T19:00:00Z', 'participantIds' => [23358, 4], 'status' => ['type' => 'finished'], 'results' => [23358 => ['runningScore' => 2], 4 => ['runningScore' => 1]], 'tournament' => ['phaseType' => 'cup', 'phase' => 'qualification', 'stage' => 'cupQualificationRound', 'stageName' => 'Qualification', 'round' => '3']],
            ['id' => 2, 'startDate' => '2026-09-10T19:00:00Z', 'participantIds' => [23358, 2], 'status' => ['type' => 'finished'], 'results' => [23358 => ['runningScore' => 2], 2 => ['runningScore' => 1]], 'tournament' => ['phaseType' => 'group', 'phase' => 'group', 'stageName' => 'UEFA Champions League 26/27', 'round' => '1']],
            ['id' => 3, 'startDate' => '2099-10-01T19:00:00Z', 'participantIds' => [3, 23358], 'status' => ['type' => 'notStarted'], 'tournament' => ['phaseType' => 'group', 'phase' => 'group', 'stageName' => 'UEFA Champions League 26/27', 'round' => '2']],
        ]];
    }

    private function teamStandingsPayload(): array
    {
        return ['participants' => [23358 => ['name' => 'Bodø/Glimt', 'images' => ['clubLogo' => ['url' => 'https://example.test/bodo.png']]]], 'standings' => [['stageName' => 'UEFA Champions League 26/27', 'teamStandings' => [
            ['teamId' => 23358, 'rank' => 12, 'played' => 1, 'wins' => 1, 'draws' => 0, 'losses' => 0, 'goalsFor' => 2, 'goalsAgainst' => 1, 'points' => 3],
        ]]]];
    }
}
