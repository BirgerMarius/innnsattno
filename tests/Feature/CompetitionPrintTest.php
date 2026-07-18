<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CompetitionPrintTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-07-15 12:00:00', 'Europe/Oslo'));
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function eliteserien_print_contains_only_the_requested_weeks_and_the_table(): void
    {
        $this->fakeCompetition(8766);

        $response = $this->get('/eliteserien/utskrift');

        $response->assertOk()
            ->assertSee('Resultater – uke 28')
            ->assertSee('Forrige Hjem')
            ->assertSee('Neste Hjem')
            ->assertSee('Tabell')
            ->assertSee('Tabellag')
            ->assertDontSee('Utenfor Hjem')
            ->assertSee('window.print()');
    }

    /** @test */
    public function premier_league_print_contains_only_the_requested_weeks_and_the_table(): void
    {
        $this->fakeCompetition(9186);

        $response = $this->get('/premier-league/utskrift');

        $response->assertOk()
            ->assertSee('Premier League')
            ->assertSee('Resultater – uke 28')
            ->assertSee('Kamper – uke 30')
            ->assertSee('Forrige Hjem')
            ->assertSee('Neste Hjem')
            ->assertDontSee('Utenfor Hjem');
    }

    /** @test */
    public function league_pages_link_to_their_named_print_routes(): void
    {
        $this->fakeCompetition(8766);
        $this->get('/eliteserien')->assertOk()
            ->assertSee('Skriv ut ukeoversikt')
            ->assertSee(route('eliteserien.print'), false);

        Cache::flush();
        $this->fakeCompetition(9186);
        $this->get('/premier-league')->assertOk()
            ->assertSee('Skriv ut ukeoversikt')
            ->assertSee(route('premier-league.print'), false);
    }

    /** @test */
    public function empty_periods_show_norwegian_information_messages(): void
    {
        Http::fake([
            '*/schedule' => Http::response(['events' => [], 'participants' => []]),
            '*/standings' => Http::response([]),
        ]);

        $this->get('/eliteserien/utskrift')->assertOk()
            ->assertSee('Ingen ferdigspilte kamper forrige uke.')
            ->assertSee('Ingen kamper er satt opp kommende uke.')
            ->assertSee('Tabellen kunne ikke lastes akkurat nå.');
    }

    /** @test */
    public function iso_weeks_are_correct_across_new_year(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-30 12:00:00', 'Europe/Oslo'));
        $this->fakeCompetition(8766);

        $response = $this->get('/eliteserien/utskrift');

        $response->assertOk()
            ->assertViewHas('previousWeek', fn (array $week) => $week['number'] === 52 && $week['year'] === 2026)
            ->assertViewHas('nextWeek', fn (array $week) => $week['number'] === 2 && $week['year'] === 2027);
    }

    private function fakeCompetition(int $seasonId): void
    {
        $participants = [
            1 => ['name' => 'Forrige Hjem'], 2 => ['name' => 'Forrige Borte'],
            3 => ['name' => 'Neste Hjem'], 4 => ['name' => 'Neste Borte'],
            5 => ['name' => 'Utenfor Hjem'], 6 => ['name' => 'Utenfor Borte'],
            7 => ['name' => 'Tabellag'],
        ];
        $events = [
            $this->event(1, '2026-07-06T17:00:00Z', [1, 2], true),
            $this->event(2, '2026-07-12T18:00:00Z', [1, 2], true),
            $this->event(3, '2026-07-20T17:00:00Z', [3, 4], false),
            $this->event(4, '2026-07-26T18:00:00Z', [3, 4], false),
            $this->event(5, '2026-07-13T17:00:00Z', [5, 6], true),
        ];
        $standings = [
            'participants' => $participants,
            'standings' => [['teamStandings' => [[
                'teamId' => 7, 'rank' => 1, 'played' => 10,
                'goalsFor' => 20, 'goalsAgainst' => 10, 'points' => 25,
            ]]]],
        ];

        Http::fake([
            "*/tournaments/seasons/{$seasonId}/schedule" => Http::response(['participants' => $participants, 'events' => $events]),
            "*/tournaments/seasons/{$seasonId}/standings" => Http::response($standings),
        ]);
    }

    private function event(int $id, string $date, array $teams, bool $finished): array
    {
        return [
            'id' => $id,
            'startDate' => $date,
            'participantIds' => $teams,
            'status' => ['type' => $finished ? 'finished' : 'notstarted'],
            'results' => $finished ? [
                $teams[0] => ['runningScore' => 2],
                $teams[1] => ['runningScore' => 1],
            ] : [],
        ];
    }
}
