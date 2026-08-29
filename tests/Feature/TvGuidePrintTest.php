<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TvGuidePrintTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @dataProvider printRoutes
     */
    public function test_print_page_returns_to_tv_guide_after_print_dialog_closes(string $route): void
    {
        Http::fake([
            'tvguide.vg.no/*' => Http::response([], 200),
        ]);

        $this->get($route)
            ->assertOk()
            ->assertSee("window.addEventListener('afterprint'", false)
            ->assertSee('if (hasReturnedToTvGuide)', false)
            ->assertSee('window.location.replace("\\/tv")', false)
            ->assertSee('window.print()', false)
            ->assertSee('{ once: true }', false);
    }

    public function printRoutes(): array
    {
        return [
            'Ringerike print page' => ['/print'],
            'Ilseng print page' => ['/print-ilseng'],
        ];
    }

    public function test_ringerike_print_includes_viasat_explore_without_changing_ilseng_channels(): void
    {
        Http::fake([
            'tvguide.vg.no/*' => Http::response([[
                'channel' => ['name' => 'Viasat Explore', 'slug' => 'viasat-explore'],
                'listings' => [],
            ]], 200),
        ]);

        $this->get('/print')
            ->assertOk()
            ->assertSee('Viasat Explore');

        $this->get('/print-ilseng')->assertOk();

        Http::assertSent(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

            if (! isset($query['channels'])) {
                return false;
            }

            $channels = explode(',', $query['channels']);
            $documentaryChannels = array_slice($channels, array_search('national-geographic', $channels), 4);

            return $documentaryChannels === [
                'national-geographic',
                'discovery-channel',
                'viasat-explore',
                'investigation-discovery',
            ];
        });

        Http::assertSent(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

            return isset($query['channels']) && ! str_contains($query['channels'], 'viasat-explore');
        });
    }

    public function test_ringerike_print_inserts_dw_news_between_bbc_and_al_jazeera(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00', 'Europe/Oslo'));

        Http::fake([
            'tvguide.vg.no/*' => Http::response([
                ['channel' => ['name' => 'BBC World News', 'slug' => 'bbc-world-news'], 'listings' => []],
                ['channel' => ['name' => 'Al Jazeera English', 'slug' => 'al-jazeera-english'], 'listings' => []],
            ], 200),
            'www.dw.com/graph-api/en/livestream/english' => Http::response($this->dwResponse([
                ['startDate' => '2026-07-15T20:00:00Z', 'program' => ['name' => 'DW News']],
            ]), 200),
        ]);

        $this->get('/print')
            ->assertOk()
            ->assertSeeInOrder(['BBC World News', 'DW News', 'Al Jazeera English']);
    }

    public function test_ringerike_print_still_works_when_dw_is_unavailable_and_ilseng_does_not_request_dw(): void
    {
        Http::fake([
            'tvguide.vg.no/*' => Http::response([], 200),
            'www.dw.com/graph-api/en/livestream/english' => Http::response([], 503),
        ]);

        $this->get('/print')->assertOk()->assertDontSee('DW News');
        $this->get('/print-ilseng')->assertOk();

        Http::assertSentCount(3);
        Http::assertSent(fn ($request) => $request->url() === 'https://www.dw.com/graph-api/en/livestream/english');
    }

    public function test_ringerike_print_keeps_problematic_channel_and_programme_text_inside_its_column(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 08:00:00', 'Europe/Oslo'));

        $longWord = str_repeat('Programtittelutenmellomrom', 12);
        $longTitle = 'Orkanen Katrina: kampen mot katastrofen med en lang programtittel som skal brytes kontrollert over flere linjer';

        Http::fake([
            'tvguide.vg.no/*' => Http::response([
                [
                    'channel' => ['name' => 'Kanalnavn med en svært lang beskrivelse som også må holde seg i kolonnen'],
                    'listings' => [
                        ['startsAt' => '2026-08-29T20:00:00Z', 'title' => ['title' => $longTitle]],
                        ['startsAt' => '2026-08-29T21:00:00Z', 'title' => ['title' => $longWord]],
                        ['startsAt' => '2026-08-29T22:00:00Z', 'title' => ['title' => $longTitle]],
                    ],
                ],
            ], 200),
            'www.dw.com/graph-api/en/livestream/english' => Http::response([], 503),
        ]);

        $response = $this->get('/print')->assertOk();

        $response
            ->assertSee($longTitle)
            ->assertSee($longWord)
            ->assertSee('ringerike-tv-print__channel')
            ->assertSee('ringerike-tv-print__listing')
            ->assertSee('grid-template-columns: 3.15em minmax(0, 1fr)', false)
            ->assertSee('max-width: 100%', false)
            ->assertSee('min-width: 0', false)
            ->assertSee('overflow: hidden', false)
            ->assertSee('white-space: nowrap', false)
            ->assertSee('overflow-wrap: anywhere', false)
            ->assertSee('word-break: break-word', false)
            ->assertSee('line-height: 1.2', false);

        $this->assertSame(3, substr_count($response->getContent(), '<div class="ringerike-tv-print__listing">'));
    }

    public function test_ilseng_print_keeps_problematic_channel_and_programme_text_inside_its_column(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 08:00:00', 'Europe/Oslo'));

        $longWord = str_repeat('Programtittelutenmellomrom', 12);
        $longTitle = 'Orkanen Katrina: kampen mot katastrofen med en lang programtittel som skal brytes kontrollert over flere linjer';

        Http::fake([
            'tvguide.vg.no/*' => Http::response([
                [
                    'channel' => ['name' => 'Kanalnavn med en svært lang beskrivelse som også må holde seg i kolonnen'],
                    'listings' => [
                        ['startsAt' => '2026-08-29T20:00:00Z', 'title' => ['title' => $longTitle]],
                        ['startsAt' => '2026-08-29T21:00:00Z', 'title' => ['title' => $longWord]],
                        ['startsAt' => '2026-08-29T22:00:00Z', 'title' => ['title' => $longTitle]],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->get('/print-ilseng')->assertOk();

        $response
            ->assertSee($longTitle)
            ->assertSee($longWord)
            ->assertSee('ilseng-tv-print__channel')
            ->assertSee('ilseng-tv-print__listing')
            ->assertSee('grid-template-columns: 3.15em minmax(0, 1fr)', false)
            ->assertSee('max-width: 100%', false)
            ->assertSee('min-width: 0', false)
            ->assertSee('overflow: hidden', false)
            ->assertSee('white-space: nowrap', false)
            ->assertSee('overflow-wrap: anywhere', false)
            ->assertSee('word-break: break-word', false)
            ->assertSee('line-height: 1.2', false);

        $this->assertSame(3, substr_count($response->getContent(), '<div class="ilseng-tv-print__listing">'));
    }

    private function dwResponse(array $slots): array
    {
        return [
            'data' => [
                'livestreamChannels' => [[
                    'nextTimeSlots' => $slots,
                ]],
            ],
        ];
    }
}
