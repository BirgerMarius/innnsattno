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

        $this->get('/ukens-fotball-pa-tv?utvalg=utvidet')
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
            ->assertSee('name="utvalg" value="utvidet" checked', false)
            ->assertDontSee('Studio – Magasin')
            ->assertSee('Sendestart')
            ->assertSee('Avspark vises ikke');
    }

    public function test_standard_and_extended_selection_use_their_documented_competitions_and_print_keeps_it(): void
    {
        Http::fake(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $listings = ($query['date'] ?? '') === '2026-10-12' ? [
                $this->listing('Premier League', 'West Ham - Arsenal', '2026-10-12T17:00:00Z', 31),
                $this->listing('Eliteserien', 'Brann - Viking', '2026-10-12T18:00:00Z', 32),
                $this->listing('UEFA Champions League', 'Real Madrid - PSG', '2026-10-12T19:00:00Z', 33),
                $this->listing('La Liga', 'Barcelona - Sevilla', '2026-10-12T20:00:00Z', 34),
                $this->listing('Bundesliga', 'Bayern - Dortmund', '2026-10-12T21:00:00Z', 35),
                $this->listing('UEFA Europa League', 'Roma - Porto', '2026-10-12T22:00:00Z', 36),
                $this->listing('UEFA Conference League', 'Brugge - Basel', '2026-10-12T23:00:00Z', 37),
                $this->listing('UEFA Nations League', 'Norge - Sverige', '2026-10-12T12:00:00Z', 38),
                $this->listing('FIFA World Cup', 'Brasil - Japan', '2026-10-12T13:00:00Z', 39),
                $this->listing('Serie A', 'Milan - Inter', '2026-10-12T14:00:00Z', 40),
                $this->listing('OBOS-ligaen', 'Start - Moss', '2026-10-12T15:00:00Z', 41),
            ] : [];
            return Http::response([['channel' => ['name' => 'TV3+', 'slug' => 'tv3-plus'], 'listings' => $listings]]);
        });
        $this->warmTv(now('Europe/Oslo')->startOfWeek(), 7);

        $this->get('/ringerike/ukens-fotball-pa-tv')->assertOk()
            ->assertSee('West Ham')->assertSee('Brann')->assertSee('Real Madrid')->assertSee('Norge')
            ->assertDontSee('Bayern')->assertDontSee('Roma')->assertDontSee('Brugge')->assertDontSee('Brasil')->assertDontSee('Milan')
            ->assertDontSee('Barcelona')
            ->assertSee('name="utvalg" value="standard" checked', false)
            ->assertSee('TV-sendte kamper fra Eliteserien, Premier League, Nations League og Champions League i valgt periode.')
            ->assertDontSee('Alle fotballkamper i TV-guiden i valgt periode.')
            ->assertSee('data-weekly-football-print', false)
            ->assertSee('form.submit()', false)
            ->assertSee('/ringerike/ukens-fotball-pa-tv/utskrift?uke=denne&amp;utvalg=standard&amp;periode=hele', false);

        $this->get('/ringerike/ukens-fotball-pa-tv/utskrift')->assertOk()
            ->assertSee('poster--standard', false)
            ->assertSee('West Ham')->assertSee('Brann')->assertSee('Real Madrid')->assertSee('Norge')
            ->assertDontSee('Bayern')->assertDontSee('Roma')->assertDontSee('Brugge')->assertDontSee('Brasil')->assertDontSee('Milan')
            ->assertDontSee('Barcelona');

        $this->get('/ringerike/ukens-fotball-pa-tv?utvalg=utvidet')->assertOk()
            ->assertSee('Barcelona')->assertSee('Bayern')
            ->assertSee('Alle fotballkamper i TV-guiden i valgt periode.')
            ->assertDontSee('TV-sendte kamper fra Eliteserien, Premier League, Nations League og Champions League i valgt periode.')
            ->assertSee('name="utvalg" value="utvidet" checked', false)
            ->assertSee('/ringerike/ukens-fotball-pa-tv/utskrift?uke=denne&amp;utvalg=utvidet&amp;periode=hele', false);
        $this->get('/ringerike/ukens-fotball-pa-tv/utskrift?utvalg=utvidet')->assertOk()
            ->assertSee('poster--all', false)
            ->assertSee('Barcelona')->assertSee('Bayern')
            ->assertSee('/ringerike/ukens-fotball-pa-tv?uke=denne&amp;utvalg=utvidet&amp;periode=hele', false);
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
            ->assertSee('Vi har forsøkt å gjøre oversikten så korrekt og fullstendig som mulig')
            ->assertSee('class="disclaimer"', false)
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

    public function test_monday_night_uses_tv_start_time_filters_replays_and_deduplicates_the_concrete_listing(): void
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

        $this->assertSame(['Barcelona – Real Madrid', 'Før seks – Kamp'], array_column($thisWeek['mondayNightMatches'], 'name'));
        $this->assertSame(['Sendetype ikke oppgitt', 'Direkte'], array_column($thisWeek['mondayNightMatches'], 'broadcastType'));
        $this->assertSame(['Strømmen – Strømsgodset'], array_column($thisWeek['matches'], 'name'));
        $this->assertSame('Direkte', $thisWeek['matches'][0]['broadcastType']);
        $this->assertSame(['Barcelona – Real Madrid', 'Før seks – Kamp', 'Etter seks – Kamp'], array_column($nextWeek['matches'], 'name'));
        $this->assertSame([], $nextWeek['mondayNightMatches']);

        $this->get('/ringerike/ukens-fotball-pa-tv?utvalg=utvidet')->assertOk()
            ->assertSee('Natt til mandag')->assertSee('Sendetype ikke oppgitt')
            ->assertDontSee('Kampoppsummering')->assertDontSee('>Studio<', false);
        $this->get('/ringerike/ukens-fotball-pa-tv/utskrift?utvalg=utvidet')->assertOk()
            ->assertSee('Natt til mandag')->assertSee('Sendetype ikke oppgitt');
    }

    public function test_remaining_selection_uses_only_confirmed_sportsnext_status_and_keeps_live_future_and_cancelled_matches(): void
    {
        Cache::put('schibsted_sports.premier_league.9186.last_good', [
            'sourceFetchedAt' => '2026-10-14T11:55:00+02:00',
            'matches' => [
                ['homeTeam' => 'Arsenal', 'awayTeam' => 'Chelsea', 'startsAt' => Carbon::parse('2026-10-12 20:00', 'Europe/Oslo'), 'isFinished' => true, 'homeScore' => 2, 'awayScore' => 1, 'statusLabel' => 'Ferdig'],
                ['homeTeam' => 'Man City', 'awayTeam' => 'Newcastle', 'startsAt' => Carbon::parse('2026-10-13 20:00', 'Europe/Oslo'), 'isFinished' => true, 'homeScore' => null, 'awayScore' => null, 'statusLabel' => 'Ferdig'],
                ['homeTeam' => 'Liverpool', 'awayTeam' => 'Everton', 'startsAt' => Carbon::parse('2026-10-14 20:00', 'Europe/Oslo'), 'isFinished' => false, 'homeScore' => null, 'awayScore' => null, 'statusLabel' => 'Pågår'],
                ['homeTeam' => 'Brentford', 'awayTeam' => 'Wolves', 'startsAt' => Carbon::parse('2026-10-16 20:00', 'Europe/Oslo'), 'isFinished' => false, 'homeScore' => null, 'awayScore' => null, 'statusLabel' => 'Ikke startet'],
                ['homeTeam' => 'Villa', 'awayTeam' => 'Leeds', 'startsAt' => Carbon::parse('2026-10-17 20:00', 'Europe/Oslo'), 'isFinished' => false, 'homeScore' => null, 'awayScore' => null, 'statusLabel' => 'Avlyst'],
            ],
        ], now()->addDay());
        Http::fake(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $listings = ($query['date'] ?? '') === '2026-10-12' ? [
                $this->listing('Premier League', 'Arsenal - Chelsea', '2026-10-12T18:00:00Z', 51),
            ] : (($query['date'] ?? '') === '2026-10-13' ? [
                $this->listing('Premier League', 'Man City - Newcastle', '2026-10-13T18:00:00Z', 55),
            ] : (($query['date'] ?? '') === '2026-10-14' ? [
                $this->listing('Premier League', 'Liverpool - Everton', '2026-10-14T18:00:00Z', 52),
            ] : (($query['date'] ?? '') === '2026-10-16' ? [
                $this->listing('Premier League', 'Brentford - Wolves', '2026-10-16T18:00:00Z', 53),
            ] : (($query['date'] ?? '') === '2026-10-17' ? [
                $this->listing('Premier League', 'Villa - Leeds', '2026-10-17T18:00:00Z', 54),
            ] : []))));
            return Http::response([['channel' => ['name' => 'TV3+', 'slug' => 'tv3-plus'], 'listings' => $listings]]);
        });
        $this->warmTv(now('Europe/Oslo')->startOfWeek(), 7);

        $this->get('/ringerike/ukens-fotball-pa-tv')->assertOk()
            ->assertSee('Sluttresultat 2–1')->assertSee('Pågår')->assertSee('Ikke startet')->assertSee('Avlyst');
        $this->get('/ringerike/ukens-fotball-pa-tv?periode=gjenstaende')->assertOk()
            ->assertDontSee('Arsenal')->assertDontSee('Man City')->assertSee('Liverpool')->assertSee('Brentford')->assertSee('Villa')
            ->assertSee('Bare gjenstående kamper')->assertSee('Kamper uten bekreftet sluttstatus kan fortsatt vises.');
        $this->get('/ringerike/ukens-fotball-pa-tv/utskrift?periode=gjenstaende')->assertOk()
            ->assertDontSee('Arsenal')->assertSee('Liverpool')->assertSee('Pågår')->assertSee('Avlyst')
            ->assertDontSee('Hele uken')->assertSee('Kamper uten bekreftet sluttstatus kan fortsatt vises.');
        $this->get('/ringerike/ukens-fotball-pa-tv?utvalg=utvidet&periode=gjenstaende')->assertOk()
            ->assertDontSee('Arsenal')->assertDontSee('Man City')->assertSee('Liverpool')->assertSee('Bare gjenstående kamper')
            ->assertSee('Kamper uten bekreftet sluttstatus kan fortsatt vises.');
        $this->get('/ringerike/ukens-fotball-pa-tv/utskrift?utvalg=utvidet&periode=gjenstaende')->assertOk()
            ->assertDontSee('Arsenal')->assertDontSee('Man City')->assertSee('Liverpool')->assertSee('Utvalg: Utvidet · Bare gjenstående kamper')
            ->assertSee('Kamper uten bekreftet sluttstatus kan fortsatt vises.');
    }

    public function test_replay_evidence_is_filtered_without_guessing_from_an_older_sportsnext_fixture_or_repeated_teams(): void
    {
        Cache::put('schibsted_sports.premier_league.9186.last_good', ['matches' => [
            ['homeTeam' => 'Arsenal', 'awayTeam' => 'Chelsea', 'startsAt' => Carbon::parse('2026-10-10 20:00', 'Europe/Oslo'), 'isFinished' => true, 'homeScore' => 1, 'awayScore' => 0, 'statusLabel' => 'Ferdig'],
        ]], now()->addDay());
        Http::fake(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $listings = match ($query['date'] ?? '') {
                '2026-10-11' => [array_merge($this->listing('Premier League', 'Arsenal - Chelsea', '2026-10-11T18:00:00Z', 65), ['endsAt' => '2026-10-11T20:00:00Z'])],
                '2026-10-12' => [
                    array_merge($this->listing('Premier League', 'Arsenal - Chelsea', '2026-10-12T18:00:00Z', 61), ['isRerun' => true]),
                    array_merge($this->listing('Premier League', 'Arsenal - Chelsea', '2026-10-12T20:00:00Z', 62), ['description' => 'Reprise fra lørdag']),
                    $this->listing('Premier League', 'Arsenal - Chelsea', '2026-10-12T22:00:00Z', 63),
                    array_merge($this->listing('Premier League', 'Arsenal - Chelsea', '2026-10-12T23:00:00Z', 65), ['isLive' => false]),
                ],
                '2026-10-15' => [$this->listing('Premier League', 'Arsenal - Chelsea', '2026-10-15T18:00:00Z', 64)],
                default => [],
            };
            return Http::response([['channel' => ['name' => 'TV3+', 'slug' => 'tv3-plus'], 'listings' => $listings]]);
        });
        $this->warmTv(now('Europe/Oslo')->startOfWeek()->subDay(), 1);
        $this->warmTv(now('Europe/Oslo')->startOfWeek(), 7);

        $service = app(\App\Services\TvGuideService::class);
        $matches = $service->getWeeklyFootballMatches(now('Europe/Oslo'), 'ringerike', 'weekly-test');
        $this->assertSame(['Arsenal – Chelsea', 'Arsenal – Chelsea'], array_column($matches['matches'], 'name'));
        $this->assertSame(['2026-10-13 00:00', '2026-10-15 20:00'], array_map(fn (array $match) => $match['startsAt']->format('Y-m-d H:i'), $matches['matches']));
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
