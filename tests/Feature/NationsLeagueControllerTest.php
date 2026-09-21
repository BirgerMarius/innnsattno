<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NationsLeagueControllerTest extends TestCase
{
    /** @test */
    public function nations_league_page_renders_norways_group_fixtures_and_results(): void
    {
        Cache::flush();
        Http::fake([
            '*/tournaments/seasons/8889/schedule' => Http::response($this->schedule(), 200),
            '*/tournaments/seasons/8889/standings' => Http::response($this->standings(), 200),
            'tvguide.vg.no/*' => Http::response([], 200),
        ]);

        $this->get('/nations-league')->assertOk()
            ->assertSee('UEFA Nations League')->assertSee('League A, Group 4')
            ->assertSee('Norge')->assertSee('Danmark')->assertSee('Portugal')
            ->assertSee('Nedrykkskvalifisering')->assertSee('Kommende kamper')->assertSee('Resultater')
            ->assertSee('nl-norway', false);
        $this->get('/nations-league/utskrift')->assertOk()->assertSee('Norges gruppe')->assertSee('@page');
    }

    /** @test */
    public function nations_league_page_handles_api_failure_without_crashing(): void
    {
        Cache::flush();
        Http::fake(['*/tournaments/seasons/8889/*' => Http::response([], 503), 'tvguide.vg.no/*' => Http::response([], 200)]);
        $this->get('/nations-league')->assertOk()->assertSee('Vi klarer ikke hente oppdaterte Nations League-data akkurat nå.');
    }

    private function schedule(): array
    {
        return ['tournamentSeason' => ['name' => 'UEFA Nations League 2026/2028'], 'participants' => [11667 => ['name' => 'Norge'], 11662 => ['name' => 'Danmark'], 11496 => ['name' => 'Portugal']], 'events' => [
            ['id' => 1, 'startDate' => '2099-09-24T18:45:00Z', 'participantIds' => [11667, 11662], 'status' => ['type' => 'notStarted'], 'tournament' => ['phaseType' => 'group', 'phase' => 'group', 'groupName' => 'A4', 'stageName' => 'League A, Group 4', 'round' => '1']],
            ['id' => 2, 'startDate' => '2026-09-20T18:45:00Z', 'participantIds' => [11496, 11667], 'status' => ['type' => 'finished'], 'results' => [11496 => ['runningScore' => 1], 11667 => ['runningScore' => 2]], 'tournament' => ['phaseType' => 'group', 'phase' => 'group', 'groupName' => 'A4', 'stageName' => 'League A, Group 4', 'round' => '1']],
        ]];
    }

    private function standings(): array
    {
        return ['participants' => [11667 => ['name' => 'Norge'], 11662 => ['name' => 'Danmark'], 11496 => ['name' => 'Portugal']], 'standings' => [
            ['groupName' => 'A4', 'stageName' => 'League A, Group 4', 'teamStandings' => [
                ['teamId' => 11496, 'rank' => 1, 'played' => 1, 'wins' => 1, 'draws' => 0, 'losses' => 0, 'goalsFor' => 2, 'goalsAgainst' => 1, 'points' => 3],
                ['teamId' => 11667, 'rank' => 2, 'played' => 1, 'wins' => 1, 'draws' => 0, 'losses' => 0, 'goalsFor' => 2, 'goalsAgainst' => 1, 'points' => 3, 'rule' => ['name' => 'Nedrykkskvalifisering']],
                ['teamId' => 11662, 'rank' => 3, 'played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'goalsFor' => 0, 'goalsAgainst' => 0, 'points' => 0],
            ]],
        ]];
    }
}
