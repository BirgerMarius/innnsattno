<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PremierLeagueControllerTest extends TestCase
{
    /** @test */
    public function test_page_exposes_verified_premier_league_configuration_and_counts(): void
    {
        Cache::flush();

        Http::fake([
            '*/tournaments/seasons/9186/schedule' => Http::response($this->schedulePayload(), 200),
            '*/tournaments/seasons/9186/standings' => Http::response($this->standingsPayload(), 200),
        ]);

        $response = $this->get('/premier-league/test');

        $response->assertOk();
        $response->assertViewHas('tournamentId', 3);
        $response->assertViewHas('seasonId', 9186);
        $response->assertViewHas('apiConfigured', true);
        $response->assertViewHas('standingCount', 20);
        $response->assertViewHas('fixtureCount', 12);
        $response->assertViewHas('resultCount', 12);
        $response->assertSee('Schibsted turnerings-ID');
        $response->assertSee('9186');

        $this->get('/premier-league')->assertOk()
            ->assertSee('Trykk på lagnavnet for å se hele sesongens kamper')
            ->assertDontSee('Beta');
    }

    /** @test */
    public function screen_page_shows_up_to_three_cached_tv3_plus_matches_between_the_hero_and_table(): void
    {
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:00', 'Europe/Oslo'));
        $listings = array_map(fn (int $number) => [
            'title' => ['type' => 'sportsTitle', 'slug' => 'premier-league', 'title' => 'Premier League'],
            'sportsEvent' => ['id' => $number, 'name' => 'Hjemmelag '.$number.' - Bortelag '.$number],
            'startsAt' => sprintf('2026-09-10T%02d:55:00Z', 17 + $number),
            'isLive' => true,
            'isRerun' => false,
        ], range(1, 4));

        Http::fake([
            '*/tournaments/seasons/9186/schedule' => Http::response($this->schedulePayload(), 200),
            '*/tournaments/seasons/9186/standings' => Http::response($this->standingsPayload(), 200),
            'tvguide.vg.no/*' => Http::response([['channel' => ['slug' => 'tv3-plus'], 'listings' => $listings]], 200),
        ]);

        $response = $this->get('/premier-league')->assertOk()
            ->assertSeeInOrder(['Direktesendt Premier League på TV3+', 'Tabell'])
            ->assertSee('Hjemmelag 1 – Bortelag 1')
            ->assertSee('Hjemmelag 3 – Bortelag 3')
            ->assertDontSee('Hjemmelag 4 – Bortelag 4')
            ->assertSee('Sendestart 20.55')
            ->assertSee('TV3+')
            ->assertSee('.pl-tv3-plus-match { align-items: flex-start; flex-direction: column;', false);

        $this->assertSame(3, substr_count($response->getContent(), '<article class="pl-tv3-plus-match">'));
        Carbon::setTestNow();
    }

    private function schedulePayload(): array
    {
        $participants = [];

        for ($i = 1; $i <= 20; $i++) {
            $participants[$i] = ['name' => 'Team '.$i];
        }

        $events = [];

        for ($i = 1; $i <= 380; $i++) {
            $homeId = (($i - 1) % 20) + 1;
            $awayId = ($i % 20) + 1;
            $finished = $i <= 190;

            $events[] = [
                'id' => $i,
                'startDate' => $finished ? '2026-05-01T12:00:00Z' : '2099-08-01T12:00:00Z',
                'participantIds' => [$homeId, $awayId],
                'status' => ['type' => $finished ? 'finished' : 'notstarted'],
                'results' => [
                    $homeId => ['runningScore' => 1],
                    $awayId => ['runningScore' => 0],
                ],
            ];
        }

        return [
            'events' => $events,
            'participants' => $participants,
            'tournament' => ['id' => 3, 'name' => 'Premier League'],
            'tournamentSeason' => ['id' => 9186, 'name' => '2026/27'],
            'countries' => [],
        ];
    }

    private function standingsPayload(): array
    {
        $participants = [];
        $teamStandings = [];

        for ($i = 1; $i <= 20; $i++) {
            $participants[$i] = ['name' => 'Team '.$i];
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
                ['groupName' => 'Premier League', 'teamStandings' => $teamStandings],
            ],
            'tournament' => ['id' => 3, 'name' => 'Premier League'],
            'tournamentSeason' => ['id' => 9186, 'name' => '2026/27'],
            'participants' => $participants,
            'countries' => [],
        ];
    }
}
