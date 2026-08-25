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
        Carbon::setTestNow(Carbon::parse('2026-08-10 09:45:00', 'Europe/Oslo'));
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function eliteserien_print_uses_exact_rolling_seven_day_windows(): void
    {
        $this->fakeRollingWindowCompetition(8766);

        $response = $this->get('/eliteserien/utskrift');

        $response->assertOk()
            ->assertViewHas('printResults', fn (array $matches) => array_column($matches, 'id') === [1, 5])
            ->assertViewHas('printFixtures', fn (array $matches) => array_column($matches, 'id') === [6, 3])
            ->assertViewHas('resultsPeriod', fn (array $period) => $period['start']->format('Y-m-d H:i:s') === '2026-08-03 09:45:00')
            ->assertViewHas('fixturesPeriod', fn (array $period) => $period['end']->format('Y-m-d H:i:s') === '2026-08-17 09:45:00')
            ->assertSee('Innenfor resultat')
            ->assertSee('Tidligere i dag')
            ->assertSee('Senere i dag')
            ->assertSee('Innenfor kommende')
            ->assertDontSee('For gammelt')
            ->assertDontSee('For langt frem')
            ->assertSee('Tabell')
            ->assertSee('Tabellag')
            ->assertSee('fixture-sections', false)
            ->assertSee('window.print()');
    }

    /** @test */
    public function premier_league_print_uses_the_same_exact_rolling_seven_day_windows(): void
    {
        $this->fakeRollingWindowCompetition(9186);

        $response = $this->get('/premier-league/utskrift');

        $response->assertOk()
            ->assertSee('Premier League')
            ->assertViewHas('printResults', fn (array $matches) => array_column($matches, 'id') === [1, 5])
            ->assertViewHas('printFixtures', fn (array $matches) => array_column($matches, 'id') === [6, 3])
            ->assertSee('Tidligere i dag')
            ->assertSee('Senere i dag')
            ->assertSee('fixture-sections', false)
            ->assertDontSee('For gammelt')
            ->assertDontSee('For langt frem');
    }

    /** @test */
    public function premier_league_print_uses_the_compact_a4_layout_variant(): void
    {
        $this->fakeRollingWindowCompetition(9186);

        $this->get('/premier-league/utskrift')->assertOk()
            ->assertSee('size: A4 portrait; margin: 10mm;', false)
            ->assertSee('competition-print competition-print--compact', false)
            ->assertSee('.competition-print--compact th,', false)
            ->assertSee('padding: .6mm 1mm;', false)
            ->assertSee('.competition-print--compact .fixture-sections { gap: 3mm; }', false)
            ->assertSee('.competition-print--compact .standings-section { margin-top: 2mm; }', false);
    }

    /** @test */
    public function eliteserien_print_keeps_the_standard_print_layout_variant(): void
    {
        $this->fakeRollingWindowCompetition(8766);

        $this->get('/eliteserien/utskrift')->assertOk()
            ->assertSee('class="competition-print"', false)
            ->assertDontSee('class="competition-print competition-print--compact"', false)
            ->assertSee('.fixture-sections { display: grid;', false)
            ->assertSee('grid-template-columns: repeat(2, minmax(0, 1fr));', false);
    }

    /** @test */
    public function competition_print_returns_to_the_correct_league_after_the_print_dialog_closes(): void
    {
        $this->fakeRollingWindowCompetition(8766);
        $this->get('/eliteserien/utskrift')->assertOk()
            ->assertSee('const returnUrl = '.json_encode(route('eliteserien.index'), JSON_UNESCAPED_SLASHES).';', false)
            ->assertSee("window.addEventListener('afterprint', returnFromPrint, { once: true });", false)
            ->assertSee('window.location.replace(returnUrl);', false);

        Cache::flush();
        $this->fakeRollingWindowCompetition(9186);
        $this->get('/premier-league/utskrift')->assertOk()
            ->assertSee('const returnUrl = '.json_encode(route('premier-league.index'), JSON_UNESCAPED_SLASHES).';', false)
            ->assertSee("window.addEventListener('afterprint', returnFromPrint, { once: true });", false)
            ->assertSee('window.location.replace(returnUrl);', false);
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
            ->assertSee('Ingen ferdigspilte kamper de siste 7 døgnene.')
            ->assertSee('Ingen kamper er satt opp de neste 7 døgnene.')
            ->assertSee('Tabellen kunne ikke lastes akkurat nå.');
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

    private function fakeRollingWindowCompetition(int $seasonId): void
    {
        $participants = [
            1 => ['name' => 'Innenfor resultat'], 2 => ['name' => 'Motstander 1'],
            3 => ['name' => 'For gammelt'], 4 => ['name' => 'Motstander 2'],
            5 => ['name' => 'Innenfor kommende'], 6 => ['name' => 'Motstander 3'],
            7 => ['name' => 'For langt frem'], 8 => ['name' => 'Motstander 4'],
            9 => ['name' => 'Tidligere i dag'], 10 => ['name' => 'Motstander 5'],
            11 => ['name' => 'Senere i dag'], 12 => ['name' => 'Motstander 6'],
            13 => ['name' => 'Tabellag'],
        ];
        $events = [
            $this->event(1, '2026-08-03T07:45:01Z', [1, 2], true),
            $this->event(2, '2026-08-03T07:44:59Z', [3, 4], true),
            $this->event(3, '2026-08-17T07:44:59Z', [5, 6], false),
            $this->event(4, '2026-08-17T07:45:01Z', [7, 8], false),
            $this->event(5, '2026-08-10T06:00:00Z', [9, 10], true),
            $this->event(6, '2026-08-10T16:00:00Z', [11, 12], false),
        ];
        $standings = [
            'participants' => $participants,
            'standings' => [['teamStandings' => [[
                'teamId' => 13, 'rank' => 1, 'played' => 10,
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
