<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TvGuidePrintTest extends TestCase
{
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

            return ! str_contains($query['channels'], 'viasat-explore');
        });
    }
}
