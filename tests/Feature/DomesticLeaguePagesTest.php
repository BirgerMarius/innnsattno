<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DomesticLeaguePagesTest extends TestCase
{
    /** @test */
    public function new_domestic_league_pages_use_verified_seasons_and_preserve_prison_specific_tv_data(): void
    {
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:00', 'Europe/Oslo'));
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'tvguide.vg.no')) {
                parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
                $isIlseng = str_contains($query['channels'] ?? '', 'v-sport-1');
                return Http::response([['channel' => ['name' => $isIlseng ? 'Viasat Explore' : 'TV 2 Sport 1'], 'listings' => [[
                    'title' => ['type' => 'sportsTitle', 'title' => 'LaLiga EA Sports'],
                    'sportsEvent' => ['id' => 44, 'name' => $isIlseng ? 'Kun Ilseng - Lag' : 'Kun Ringerike - Lag'],
                    'startsAt' => '2026-09-10T18:00:00Z', 'isLive' => true, 'isRerun' => false,
                ]]]]);
            }
            foreach ([8767 => 40, 9227 => 2, 9237 => 6] as $season => $tournament) {
                if (str_contains($request->url(), "/tournaments/seasons/{$season}/schedule")) return Http::response($this->schedule($season, $tournament), 200);
                if (str_contains($request->url(), "/tournaments/seasons/{$season}/standings")) return Http::response($this->standings($season, $tournament), 200);
            }
            return Http::response([], 404);
        });
        foreach ([8767, 9227, 9237] as $season) $this->warmCompetition($season);
        $this->warmTv(now('Europe/Oslo'), 7, 'ringerike');
        $this->warmTv(now('Europe/Oslo'), 7, 'ilseng');

        $this->get('/ringerike/obos-ligaen')->assertOk()->assertSee('OBOS-ligaen 2026')->assertSee('Tabell')->assertSee('/ringerike/obos-ligaen/lag/1', false);
        $this->get('/ringerike/la-liga')->assertOk()->assertSee('La Liga 2026/27')->assertSee('Kun Ringerike – Lag')->assertSee('Kanalutvalg: Ringerike fengsel');
        $this->get('/ilseng/la-liga?fengsel=ringerike')->assertOk()->assertSee('Kun Ilseng – Lag')->assertDontSee('Kun Ringerike – Lag')->assertSee('Kanalutvalg: Ilseng fengsel');
        $this->get('/kongsvinger/bundesliga')->assertOk()->assertSee('Bundesliga 2026/27')->assertSee('Kanalutvalg: Kongsvinger fengsel');
        $this->get('/ringerike/bundesliga/utskrift')->assertOk()->assertSee('Bundesliga')->assertSee('const returnUrl = "/ringerike/bundesliga";', false);
        Carbon::setTestNow();
    }

    /** @test */
    public function stale_cached_domestic_league_data_is_shown_when_a_refresh_fails(): void
    {
        Cache::flush();
        Http::fake(['*/tournaments/seasons/9237/*' => Http::response([], 500)]);
        Cache::forever('schibsted_sports.bundesliga.9237.last_good', [
            'standings' => [], 'standingsGroups' => [], 'teams' => [], 'matches' => [], 'upcomingFixtures' => [], 'recentResults' => [],
            'lastUpdated' => null, 'sourceFetchedAt' => now()->subHours(7)->toIso8601String(), 'apiConfigured' => true, 'apiError' => false, 'usingStaleData' => false,
        ]);
        $this->get('/ringerike/bundesliga')->assertOk()->assertSee('Viser sist lagrede data.');
    }

    private function schedule(int $season, int $tournament): array
    {
        return ['events' => [[
            'id' => 1, 'startDate' => '2099-08-01T12:00:00Z', 'participantIds' => [1, 2], 'status' => ['type' => 'notstarted'],
            'results' => [],
        ], ['id' => 2, 'startDate' => '2026-08-01T12:00:00Z', 'participantIds' => [1, 2], 'status' => ['type' => 'finished'], 'results' => [1 => ['runningScore' => 1], 2 => ['runningScore' => 0]]]],
            'participants' => [1 => ['name' => 'Lag 1'], 2 => ['name' => 'Lag 2']], 'tournament' => ['id' => $tournament], 'tournamentSeason' => ['id' => $season]];
    }

    private function standings(int $season, int $tournament): array
    {
        return ['standings' => [['teamStandings' => [['teamId' => 1, 'rank' => 1, 'played' => 1, 'wins' => 1, 'draws' => 0, 'losses' => 0, 'goalsFor' => 1, 'goalsAgainst' => 0, 'points' => 3]]]],
            'participants' => [1 => ['name' => 'Lag 1'], 2 => ['name' => 'Lag 2']], 'tournament' => ['id' => $tournament], 'tournamentSeason' => ['id' => $season]];
    }
}
