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

        $this->get('/ukens-fotball-pa-tv?vis_alle=1')
            ->assertOk()
            ->assertSee('class="site-logo"', false)
            ->assertSee('aria-label="Gå til forsiden"', false)
            ->assertSee('class="site-footer ', false)
            ->assertSee('mailto:innsatt@innsatt.no', false)
            ->assertSee('Uke 42')
            ->assertSee('Mandag 12. oktober')
            ->assertSee('Søndag 18. oktober')
            ->assertSee('West Ham')->assertSee('Arsenal')
            ->assertSee('Barcelona')->assertSee('Sevilla')
            ->assertSee('Bayern')->assertSee('Dortmund')
            ->assertSee('La Liga')
            ->assertSee('TV3+')->assertSee('V sport 1')
            ->assertSee('name="vis_alle" value="1" checked', false)
            ->assertDontSee('Studio – Magasin')
            ->assertSee('Sendestart')
            ->assertSee('Avspark vises ikke');
    }

    public function test_default_selection_only_shows_premier_league_eliteserien_and_champions_league_and_print_keeps_it(): void
    {
        Http::fake(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $listings = ($query['date'] ?? '') === '2026-10-12' ? [
                $this->listing('Premier League', 'West Ham - Arsenal', '2026-10-12T17:00:00Z', 31),
                $this->listing('Eliteserien', 'Brann - Viking', '2026-10-12T18:00:00Z', 32),
                $this->listing('UEFA Champions League', 'Real Madrid - PSG', '2026-10-12T19:00:00Z', 33),
                $this->listing('La Liga', 'Barcelona - Sevilla', '2026-10-12T20:00:00Z', 34),
                $this->listing('Bundesliga', 'Bayern - Dortmund', '2026-10-12T21:00:00Z', 35),
            ] : [];
            return Http::response([['channel' => ['name' => 'TV3+', 'slug' => 'tv3-plus'], 'listings' => $listings]]);
        });
        $this->warmTv(now('Europe/Oslo')->startOfWeek(), 7);

        $this->get('/ringerike/ukens-fotball-pa-tv')->assertOk()
            ->assertSee('West Ham')->assertSee('Brann')->assertSee('Real Madrid')
            ->assertDontSee('Barcelona')->assertDontSee('Bayern')
            ->assertSee('name="vis_alle" value="1"', false)
            ->assertDontSee('name="vis_alle" value="1" checked', false)
            ->assertSee('/ringerike/ukens-fotball-pa-tv/utskrift?uke=denne', false);

        $this->get('/ringerike/ukens-fotball-pa-tv/utskrift')->assertOk()
            ->assertSee('poster--standard', false)
            ->assertSee('West Ham')->assertSee('Brann')->assertSee('Real Madrid')
            ->assertDontSee('Barcelona')->assertDontSee('Bayern');

        $this->get('/ringerike/ukens-fotball-pa-tv?vis_alle=1')->assertOk()
            ->assertSee('Barcelona')->assertSee('Bayern')
            ->assertSee('/ringerike/ukens-fotball-pa-tv/utskrift?uke=denne&amp;vis_alle=1', false);
        $this->get('/ringerike/ukens-fotball-pa-tv/utskrift?vis_alle=1')->assertOk()
            ->assertSee('poster--all', false)
            ->assertSee('Barcelona')->assertSee('Bayern')
            ->assertSee('/ringerike/ukens-fotball-pa-tv?uke=denne&amp;vis_alle=1', false);
    }

    public function test_weekly_page_reports_missing_local_data_without_claiming_the_source_fetch_failed(): void
    {
        Http::fake(['tvguide.vg.no/*' => Http::response([], 503)]);

        $this->get('/ukens-fotball-pa-tv')->assertOk()
            ->assertSee('Lokale TV-data mangler for 7 av 7 dager')
            ->assertDontSee('TV-guiden kunne ikke hentes')
            ->assertDontSee('Ingen direktesendte kamper fra de valgte ligaene');

        // A page view is cache-only, even when the source would fail.
        Http::assertNothingSent();
    }

    public function test_next_week_keeps_prison_selection_and_crosses_the_year_boundary(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-30 12:00:00', 'Europe/Oslo'));
        Http::fake(['tvguide.vg.no/*' => Http::response([['channel' => ['name' => 'TV3+'], 'listings' => []]])]);

        $this->get('/ilseng/ukens-fotball-pa-tv?fengsel=ringerike&uke=neste')->assertOk()
            ->assertSee('Neste ukes TV-sendte fotballkamper')
            ->assertSee('Uke 1')
            ->assertSee('4. januar–10. januar 2027')
            ->assertSee('Ilseng fengsel')
            ->assertSee('/ilseng/ukens-fotball-pa-tv?uke=neste', false)
            ->assertSee('weekly-football-selector', false);
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

        $this->get('/ilseng/ukens-fotball-pa-tv/utskrift?fengsel=ringerike')->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, follow')
            ->assertSee('@page { size: A4 landscape;', false)
            ->assertSee('UKAS KAMPER')
            ->assertSee('football-watermark.svg', false)
            ->assertSee('Kanalutvalg: Ilseng fengsel')
            ->assertSee('Sist dataoppdatering:')
            ->assertSee('break-inside:avoid', false)
            ->assertSee('poster--standard', false)
            ->assertDontSee('class="site-logo"', false)
            ->assertDontSee('class="site-footer ', false)
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

    public function test_monday_night_uses_tv_start_time_keeps_replays_and_deduplicates_the_concrete_listing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-25 12:00:00', 'Europe/Oslo'));
        Http::fake(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $date = $query['date'] ?? '';
            $listings = match ($date) {
                '2026-10-25', '2026-10-26' => [
                    $this->listingWithMetadata('LaLiga EA Sports', 'Barcelona - Real Madrid', '2026-10-25T23:00:00Z', 780216, 498227512, false, false, 36804, 'la-liga'),
                    $this->listingWithMetadata('LaLiga EA Sports', 'Real Sociedad - Levante', '2026-10-26T01:00:00Z', 780217, 498227513, false, true, 36804, 'la-liga'),
                    $this->listingWithMetadata('LaLiga EA Sports', 'Celta - Real Betis', '2026-10-26T03:00:00Z', 780218, 498227514, false, true, 36804, 'la-liga'),
                    $this->listingWithMetadata('LaLiga EA Sports', 'Før seks - Kamp', '2026-10-26T04:59:00Z', 780219, 498227515, true, false, 36804, 'la-liga'),
                    $this->listingWithMetadata('LaLiga EA Sports', 'Etter seks - Kamp', '2026-10-26T05:00:00Z', 780220, 498227516, true, false, 36804, 'la-liga'),
                    $this->listingWithMetadata('LaLiga EA Sports', 'Barcelona - Real Madrid', '2026-10-26T06:00:00Z', 780216, 498610213, false, true, 36804, 'la-liga'),
                    $this->listingWithMetadata('OBOS-ligaen', 'Begge flagg - Kamp', '2026-10-25T13:00:00Z', 777068, 498227517, true, true, 78710, 'obos-ligaen-1'),
                    $this->listingWithMetadata('OBOS-ligaen', 'Strømmen - Strømsgodset', '2026-10-25T11:55:00Z', 777067, 498227505, true, false, 78710, 'obos-ligaen-1'),
                    $this->listingWithMetadata('OBOS-ligaen: Studio', 'Studio', '2026-10-25T11:40:00Z', 782360, 498227504, true, false, 35627, 'obos-ligaen-studio'),
                    $this->listingWithMetadata('UEFA Champions League', 'Kampoppsummering', '2026-10-25T11:00:00Z', 780814, 498610330, false, true, 668379, 'uefa-champions-league-summary'),
                ],
                default => [],
            };
            return Http::response([['channel' => ['name' => 'TV 2 Sport 1', 'slug' => 'tv2-sport-1'], 'listings' => $listings]]);
        });

        $this->warmTv(Carbon::parse('2026-10-19', 'Europe/Oslo'), 8);
        $service = app(\App\Services\TvGuideService::class);
        $thisWeek = $service->getWeeklyFootballMatches(now('Europe/Oslo'), 'ringerike', 'weekly-test');
        $nextWeek = $service->getWeeklyFootballMatches(now('Europe/Oslo'), 'ringerike', 'weekly-test', 1);

        $this->assertSame(['Barcelona – Real Madrid', 'Real Sociedad – Levante', 'Celta – Real Betis', 'Før seks – Kamp'], array_column($thisWeek['mondayNightMatches'], 'name'));
        $this->assertSame(['Sendetype ikke oppgitt', 'Reprise', 'Reprise', 'Direkte'], array_column($thisWeek['mondayNightMatches'], 'broadcastType'));
        $this->assertSame(['Strømmen – Strømsgodset', 'Begge flagg – Kamp'], array_column($thisWeek['matches'], 'name'));
        $this->assertSame('Direkte', $thisWeek['matches'][0]['broadcastType']);
        $this->assertSame('Reprise', $thisWeek['matches'][1]['broadcastType']);
        $this->assertSame(['Barcelona – Real Madrid', 'Real Sociedad – Levante', 'Celta – Real Betis', 'Før seks – Kamp', 'Etter seks – Kamp', 'Barcelona – Real Madrid'], array_column($nextWeek['matches'], 'name'));
        $this->assertSame([], $nextWeek['mondayNightMatches']);

        $this->get('/ringerike/ukens-fotball-pa-tv?vis_alle=1')->assertOk()
            ->assertSee('Natt til mandag')->assertSee('Sendetype ikke oppgitt')->assertSee('Reprise')
            ->assertDontSee('Kampoppsummering')->assertDontSee('>Studio<', false);
        $this->get('/ringerike/ukens-fotball-pa-tv/utskrift?vis_alle=1')->assertOk()
            ->assertSee('Natt til mandag')->assertSee('Sendetype ikke oppgitt')->assertSee('Reprise');
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

    private function listingWithMetadata(string $title, string $name, string $startsAt, int $eventId, int $listingId, bool $isLive, bool $isRerun, int $titleId, string $slug): array
    {
        return [
            'id' => $listingId,
            'title' => ['id' => $titleId, 'type' => 'sportsTitle', 'title' => $title, 'slug' => $slug],
            'sportsEvent' => ['id' => $eventId, 'name' => $name],
            'startsAt' => $startsAt,
            'isLive' => $isLive,
            'isRerun' => $isRerun,
        ];
    }
}
