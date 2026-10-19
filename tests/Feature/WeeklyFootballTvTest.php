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

        $this->warmTv(now('Europe/Oslo')->startOfWeek(), 7);

        $this->get('/ukens-fotball-pa-tv')
            ->assertOk()
            ->assertSee('Uke 42')
            ->assertSee('Mandag 12. oktober')
            ->assertSee('Søndag 18. oktober')
            ->assertSee('West Ham')->assertSee('Arsenal')
            ->assertSee('Barcelona')->assertSee('Sevilla')
            ->assertSee('Bayern')->assertSee('Dortmund')
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
            ->assertSee('TV-guiden kunne ikke hentes for 7 av 7 dager')
            ->assertDontSee('Ingen direktesendte kamper fra de valgte ligaene');
    }

    public function test_next_week_keeps_prison_selection_and_crosses_the_year_boundary(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-30 12:00:00', 'Europe/Oslo'));
        Http::fake(['tvguide.vg.no/*' => Http::response([['channel' => ['name' => 'TV3+'], 'listings' => []]])]);

        $this->get('/ukens-fotball-pa-tv?fengsel=ilseng&uke=neste')->assertOk()
            ->assertSee('Neste ukes TV-sendte fotballkamper')
            ->assertSee('Uke 1')
            ->assertSee('4. januar–10. januar 2027')
            ->assertSee('Ilseng fengsel')
            ->assertSee('uke=neste', false)
            ->assertSee('fengsel=ilseng', false);
    }

    public function test_exact_cached_sportsnext_names_add_logos_but_missing_teams_do_not_render_empty_images(): void
    {
        Cache::put('schibsted_sports.premier_league.9186.last_good', ['teams' => [
            1 => ['teamId' => 1, 'teamName' => 'West Ham', 'emblemUrl' => 'https://example.test/west-ham.png'],
            2 => ['teamId' => 2, 'teamName' => 'Arsenal', 'emblemUrl' => 'https://example.test/arsenal.png'],
        ]], now()->addDay());
        Http::fake(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $listings = ($query['date'] ?? '') === '2026-10-12' ? [$this->listing('Premier League', 'West Ham - Arsenal', '2026-10-12T17:00:00Z', 22)] : [];
            return Http::response([['channel' => ['name' => 'TV3+'], 'listings' => $listings]]);
        });

        $this->warmTv(now('Europe/Oslo')->startOfWeek(), 7);

        $this->get('/ukens-fotball-pa-tv')->assertOk()
            ->assertSee('https://example.test/west-ham.png', false)
            ->assertSee('https://example.test/arsenal.png', false)
            ->assertDontSee('src=""', false);
    }

    public function test_print_has_a4_layout_noindex_and_selected_prison(): void
    {
        Http::fake(['tvguide.vg.no/*' => Http::response([['channel' => ['name' => 'V sport 1'], 'listings' => []]])]);

        $this->get('/ukens-fotball-pa-tv/utskrift?fengsel=ilseng')->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, follow')
            ->assertSee('@page { size: A4 landscape;', false)
            ->assertSee('UKAS KAMPER')
            ->assertSee('football-watermark.svg', false)
            ->assertSee('Kanalutvalg: Ilseng fengsel')
            ->assertSee('Sist dataoppdatering:')
            ->assertSee('break-inside:avoid', false)
            ->assertSee('window.print()', false);
    }

    public function test_dense_print_uses_columns_before_reducing_type_size(): void
    {
        Http::fake(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $listings = [];
            if (($query['date'] ?? '') === '2026-10-12') {
                foreach (range(1, 17) as $number) {
                    $listings[] = $this->listing('Premier League', 'Lag '.$number.' - Motstander '.$number, sprintf('2026-10-12T%02d:00:00Z', $number % 20), $number + 100);
                }
            }
            return Http::response([['channel' => ['name' => 'TV3+'], 'listings' => $listings]]);
        });

        $this->warmTv(now('Europe/Oslo')->startOfWeek(), 7);

        $this->get('/ukens-fotball-pa-tv/utskrift')->assertOk()
            ->assertSee('days--three', false)
            ->assertSee('days--dense', false)
            ->assertSee('Lag 17')
            ->assertSee('break-inside:avoid', false);
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
