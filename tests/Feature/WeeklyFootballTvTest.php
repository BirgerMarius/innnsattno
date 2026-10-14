<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeeklyFootballTvTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-10-14 12:00:00', 'Europe/Oslo'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_weekly_page_keeps_past_days_groups_matches_and_merges_channels(): void
    {
        Http::fake(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $date = $query['date'] ?? '';
            $listings = $date === '2026-10-12' ? [
                $this->listing('Premier League', 'West Ham - Arsenal', '2026-10-12T17:00:00Z', 11),
                $this->listing('Premier League', 'West Ham - Arsenal', '2026-10-12T17:00:00Z', 11),
                $this->listing('La Liga', 'Barcelona - Sevilla', '2026-10-12T19:00:00Z', 12),
                $this->listing('Serie A', 'Studio - Magasin', '2026-10-12T20:00:00Z', 13),
                array_merge($this->listing('Bundesliga', 'Bayern - Dortmund', '2026-10-12T21:00:00Z', 14), ['isRerun' => true]),
            ] : ($date === '2026-10-18' ? [
                $this->listing('Bundesliga', 'Bayern - Dortmund', '2026-10-18T17:00:00Z', 15),
            ] : []);

            if ($listings === []) {
                return Http::response([['channel' => ['name' => 'TV3+', 'slug' => 'tv3-plus'], 'listings' => []]]);
            }

            return Http::response([
                ['channel' => ['name' => 'TV3+', 'slug' => 'tv3-plus'], 'listings' => $listings],
                ['channel' => ['name' => 'V sport 1', 'slug' => 'v-sport-1'], 'listings' => isset($listings[1]) ? [$listings[1]] : []],
            ]);
        });

        $this->get('/ukens-fotball-pa-tv')
            ->assertOk()
            ->assertSee('Uke 42')
            ->assertSee('Mandag 12. oktober')
            ->assertSee('Søndag 18. oktober')
            ->assertSee('West Ham – Arsenal')
            ->assertSee('Barcelona – Sevilla')
            ->assertSee('Bayern – Dortmund')
            ->assertSee('La Liga')
            ->assertSee('TV3+ · V sport 1')
            ->assertDontSee('Studio – Magasin')
            ->assertSee('Sendestart')
            ->assertSee('Avspark vises ikke');
    }

    public function test_weekly_page_reports_incomplete_source_instead_of_claiming_no_matches(): void
    {
        Http::fake(['tvguide.vg.no/*' => Http::response([], 503)]);

        $this->get('/ukens-fotball-pa-tv')->assertOk()
            ->assertSee('TV-guiden dekker ikke hele denne uken ennå eller kunne ikke hentes')
            ->assertDontSee('Ingen direktesendte kamper fra de valgte ligaene');
    }

    public function test_print_has_a4_layout_noindex_and_selected_prison(): void
    {
        Http::fake(['tvguide.vg.no/*' => Http::response([['channel' => ['name' => 'V sport 1'], 'listings' => []]])]);

        $this->get('/ukens-fotball-pa-tv/utskrift?fengsel=ilseng')->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, follow')
            ->assertSee('@page { size: A4 portrait;', false)
            ->assertSee('Kanalutvalg: Ilseng fengsel')
            ->assertSee('Sist dataoppdatering:')
            ->assertSee('break-inside: avoid', false)
            ->assertSee('window.print()', false);
    }

    private function listing(string $title, string $name, string $startsAt, int $id): array
    {
        return [
            'title' => ['type' => 'sportsTitle', 'title' => $title],
            'sportsEvent' => ['id' => $id, 'name' => $name],
            'startsAt' => $startsAt,
            'isLive' => true,
            'isRerun' => false,
        ];
    }
}
