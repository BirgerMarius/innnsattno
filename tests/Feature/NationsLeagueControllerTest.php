<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NationsLeagueControllerTest extends TestCase
{
    /** @test */
    public function nations_league_page_renders_norways_group_fixtures_and_results(): void
    {
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-09-21 10:00:00', 'Europe/Oslo'));
        Http::fake([
            '*/tournaments/seasons/8889/schedule' => Http::response($this->schedule(), 200),
            '*/tournaments/seasons/8889/standings' => Http::response($this->standings(), 200),
            'tvguide.vg.no/*' => Http::response([], 200),
        ]);

        $this->get('/nations-league')->assertOk()
            ->assertSee('UEFA Nations League')->assertSee('League A, Group 4')
            ->assertSee('Norge')->assertSee('Danmark')->assertSee('Portugal')
            ->assertSee('Nedrykkskvalifisering')->assertSee('Denne ukens kamper')->assertSee('Resultater')
            ->assertSeeInOrder(['24.09.2026', 'Norge', 'Danmark', '27.09.2026', 'Norge', 'Portugal'])->assertDontSee('Utenfor uke')
            ->assertSee('/nations-league/lag/11667', false)->assertSee('/nations-league/lag/11496', false)
            ->assertDontSee('Kanalutvalg: Ringerike fengsel')
            ->assertSee('nl-norway', false);
        $this->get('/nations-league/lag/11667')->assertOk()->assertSee('Norge')->assertSee('League A, Group 4')->assertSee('Portugal');
        $this->get('/nations-league/lag/11496')->assertOk()->assertSee('Portugal')->assertSee('Norge');
        $this->get('/nations-league/lag/999999')->assertNotFound();
        $this->get('/nations-league/utskrift')->assertOk()->assertSee('Norges gruppe')->assertSee('@page')->assertDontSee('Kanalutvalg: Ringerike fengsel');
        Carbon::setTestNow();
    }

    /** @test */
    public function nations_league_page_handles_api_failure_without_crashing(): void
    {
        Cache::flush();
        Http::fake(['*/tournaments/seasons/8889/*' => Http::response([], 503), 'tvguide.vg.no/*' => Http::response([], 200)]);
        $this->get('/nations-league')->assertOk()->assertSee('Vi klarer ikke hente oppdaterte Nations League-data akkurat nå.');
    }

    /** @test */
    public function nations_league_print_uses_the_same_match_flag_data_for_results_and_fixtures(): void
    {
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-09-21 10:00:00', 'Europe/Oslo'));
        Http::fake([
            '*/tournaments/seasons/8889/schedule' => Http::response($this->schedule(), 200),
            '*/tournaments/seasons/8889/standings' => Http::response($this->standings(), 200),
            'tvguide.vg.no/*' => Http::response([], 200),
        ]);

        try {
            $this->get('/nations-league')->assertOk()
                ->assertSee('https://example.test/norge.png', false)
                ->assertSee('https://example.test/danmark.png', false);

            $this->get('/nations-league/utskrift')->assertOk()
                ->assertViewHas('printResults', function (array $matches) {
                    $this->assertNull($matches[0]['homeEmblemUrl']);
                    $this->assertStringStartsWith('https://example.test/norge.png', $matches[0]['awayEmblemUrl']);

                    return true;
                })
                ->assertViewHas('printFixtures', function (array $matches) {
                    $this->assertStringStartsWith('https://example.test/norge.png', $matches[0]['homeEmblemUrl']);
                    $this->assertStringStartsWith('https://example.test/danmark.png', $matches[0]['awayEmblemUrl']);

                    return true;
                })
                ->assertSee('Resultater og kommende kamper')
                ->assertSee('class="fixture-teams"', false)
                ->assertSee('https://example.test/norge.png', false)
                ->assertSee('https://example.test/danmark.png', false)
                ->assertSeeInOrder(['Portugal', '1–2', 'Norge'])
                ->assertSeeInOrder(['Norge', '–', 'Danmark'])
                ->assertSee('.fixture-teams { overflow-wrap: anywhere; }', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    /** @test */
    public function nations_league_screen_and_print_show_the_same_complete_current_week_tv_list(): void
    {
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-09-21 10:00:00', 'Europe/Oslo'));
        Http::fake([
            '*/tournaments/seasons/8889/schedule' => Http::response($this->schedule(), 200),
            '*/tournaments/seasons/8889/standings' => Http::response($this->standings(), 200),
            'tvguide.vg.no/*' => function ($request) {
                parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
                return Http::response(($query['date'] ?? null) === '2026-09-24' ? [[
                    'channel' => ['name' => 'TV3+', 'slug' => 'tv3-plus'],
                    'listings' => [
                        $this->tvListing('Sverige - Romania', '2026-09-24T16:00:00Z'),
                        $this->tvListing('Norge - Danmark', '2026-09-24T18:55:00Z'),
                        $this->tvListing('Tsjekkia - Kroatia', '2026-09-24T19:00:00Z'),
                        $this->tvListing('Norge - Portugal', '2026-09-27T18:55:00Z', false),
                        $this->tvListing('Studio', '2026-09-24T15:00:00Z'),
                        $this->tvListing('Utenfor uke - Lag', '2026-09-28T18:00:00Z'),
                    ],
                ]] : []);
            },
        ]);

        foreach (['/nations-league', '/nations-league/utskrift'] as $url) {
            $this->get($url)->assertOk()
                ->assertSeeInOrder(['Sverige – Romania', 'Norge – Danmark', 'Tsjekkia – Kroatia', 'Norge – Portugal'])
                ->assertDontSee('Studio')->assertDontSee('Utenfor uke')->assertDontSee('Kanalutvalg: Ringerike fengsel');
        }
        Carbon::setTestNow();
    }

    private function schedule(): array
    {
        return ['tournamentSeason' => ['name' => 'UEFA Nations League 2026/2028'], 'participants' => [11667 => ['name' => 'Norge', 'images' => ['clubLogo' => ['url' => 'https://example.test/norge.png']]], 11662 => ['name' => 'Danmark', 'images' => ['clubLogo' => ['url' => 'https://example.test/danmark.png']]], 11496 => ['name' => 'Portugal'], 11478 => ['name' => 'Wales'], 2 => ['name' => 'Utenfor uke']], 'events' => [
            ['id' => 1, 'startDate' => '2026-09-24T18:45:00Z', 'participantIds' => [11667, 11662], 'status' => ['type' => 'notStarted'], 'tournament' => ['phaseType' => 'group', 'phase' => 'group', 'groupName' => 'A4', 'stageName' => 'League A, Group 4', 'round' => '1']],
            ['id' => 3, 'startDate' => '2026-09-27T18:45:00Z', 'participantIds' => [11667, 11496], 'status' => ['type' => 'notStarted'], 'tournament' => ['phaseType' => 'group', 'phase' => 'group', 'groupName' => 'A4', 'stageName' => 'League A, Group 4', 'round' => '2']],
            ['id' => 4, 'startDate' => '2026-09-28T18:45:00Z', 'participantIds' => [11667, 2], 'status' => ['type' => 'notStarted'], 'tournament' => ['phaseType' => 'group', 'phase' => 'group', 'groupName' => 'A4', 'stageName' => 'League A, Group 4', 'round' => '3']],
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

    private function tvListing(string $name, string $startsAt, bool $isLive = true): array
    {
        return [
            'title' => ['type' => 'sportsTitle', 'title' => 'UEFA Nations League', 'slug' => 'uefa-nations-league'],
            'sportsEvent' => ['name' => $name], 'startsAt' => $startsAt, 'isLive' => $isLive, 'isRerun' => false,
        ];
    }
}
