<?php

namespace Tests\Feature;

use App\Services\TvGuideService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KongsvingerTvGuideTest extends TestCase
{
    public function test_tv_cache_is_separate_for_kongsvinger_even_with_the_same_channel_slug(): void
    {
        $date = now('Europe/Oslo')->startOfDay();
        Http::fakeSequence()
            ->push([['channel' => ['name' => 'NRK1', 'slug' => 'nrk1'], 'listings' => [['title' => ['title' => 'Ringerike-data']]]]])
            ->push([['channel' => ['name' => 'NRK1', 'slug' => 'nrk1'], 'listings' => [['title' => ['title' => 'Kongsvinger-data']]]]]);

        $service = app(TvGuideService::class);
        $service->refreshSchedule($date, ['nrk1'], 'ringerike');
        $service->refreshSchedule($date, ['nrk1'], 'kongsvinger');

        $this->assertSame('Ringerike-data', $service->getSchedule($date, ['nrk1'], 'ringerike')[0]['listings'][0]['title']['title']);
        $this->assertSame('Kongsvinger-data', $service->getSchedule($date, ['nrk1'], 'kongsvinger')[0]['listings'][0]['title']['title']);
    }

    public function test_kongsvinger_weekly_football_uses_its_own_channels_and_keeps_context_in_links_and_print(): void
    {
        Http::fake(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $date = $query['date'] ?? '';
            $listings = $date === now('Europe/Oslo')->startOfWeek()->toDateString() ? [
                $this->footballListing('Kongsvingerlaget - Motstander', 1),
            ] : [];

            return Http::response([
                ['channel' => ['name' => 'Eurosport Norge', 'slug' => 'eurosport-norge'], 'listings' => $listings],
                ['channel' => ['name' => 'TV 2 Sport 1', 'slug' => 'tv2-sport-1'], 'listings' => [
                    $this->footballListing('Utelatt lag - Ikke tilgjengelig', 2),
                ]],
            ]);
        });

        $this->warmTv(now('Europe/Oslo')->startOfWeek(), 7, 'kongsvinger');
        $schedule = app(TvGuideService::class)->getSchedule(
            now('Europe/Oslo')->startOfWeek(),
            TvGuideService::channelsForPrison('kongsvinger'),
            'kongsvinger',
        );
        $this->assertSame('Kongsvingerlaget - Motstander', $schedule[0]['listings'][0]['sportsEvent']['name']);
        $this->assertSame(['Kongsvingerlaget – Motstander'], array_column(
            app(TvGuideService::class)->getWeeklyFootballMatches(now('Europe/Oslo'), 'kongsvinger', 'test')['matches'],
            'name',
        ));

        $this->get('/kongsvinger/ukens-fotball-pa-tv?utvalg=utvidet')->assertOk()
            ->assertSee('Kongsvinger fengsel')
            ->assertSee('Kongsvingerlaget')
            ->assertSee('Eurosport Norge')
            ->assertDontSee('Utelatt lag')
            ->assertDontSee('Ringerike fengsel')
            ->assertSee('/kongsvinger/ukens-fotball-pa-tv?utvalg=utvidet', false)
            ->assertSee('/kongsvinger/ukens-fotball-pa-tv/utskrift?uke=denne&amp;utvalg=utvidet', false)
            ->assertSee('/kongsvinger/ukens-fotball-pa-tv?uke=neste&amp;utvalg=utvidet', false);

        $this->get('/kongsvinger/ukens-fotball-pa-tv/utskrift?utvalg=utvidet')->assertOk()
            ->assertSee('Kanalutvalg: Kongsvinger fengsel')
            ->assertSee('href="http://localhost:8086/kongsvinger/ukens-fotball-pa-tv?uke=denne&amp;utvalg=utvidet"', false)
            ->assertDontSee('Ringerike fengsel');

        Http::assertSent(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

            return in_array('eurosport-norge', explode(',', $query['channels'] ?? ''), true)
                && ! in_array('tv2-sport-1', explode(',', $query['channels'] ?? ''), true);
        });
    }

    public function test_kongsvinger_uses_only_the_verified_channel_selection(): void
    {
        $channels = [
            'nrk1', 'nrk2', 'nrk3',
            'tv2-direkte', 'tv2-zebra', 'tv2-livsstil', 'tv2-nyheter',
            'tvnorge', 'bbc-nordic', 'viasat-explore', 'history',
            'discovery-channel', 'investigation-discovery', 'rex', 'mtv',
            'svt1', 'svt2', 'eurosport-1', 'eurosport-norge', 'al-jazeera-english',
        ];

        $this->assertSame($channels, TvGuideService::channelsForPrison('kongsvinger'));
        $this->assertSame($channels, TvGuideService::prisonChannelSelections()['kongsvinger']);
        $this->assertNotContains('tv2-sport-1', $channels);
        $this->assertNotContains('tv2-sport-2', $channels);
        $this->assertNotContains('eurosport-2', $channels);
        $this->assertNotContains('5hd', $channels);
        $this->assertSame(config('prisons.all.ringerike.tv_channels'), TvGuideService::ringerikeChannels());
        $this->assertSame(config('prisons.all.ilseng.tv_channels'), TvGuideService::ilsengChannels());
    }

    public function test_kongsvinger_tv_print_uses_its_local_schedule_and_returns_to_kongsvinger(): void
    {
        Http::fake([
            'tvguide.vg.no/*' => Http::response([[
                'channel' => ['name' => 'SVT1', 'slug' => 'svt1'],
                'listings' => [[
                    'id' => 1,
                    'startsAt' => now('Europe/Oslo')->addHour()->toIso8601String(),
                    'title' => ['title' => 'Kongsvinger-testprogram'],
                ]],
            ]], 200),
            'contentsearch.rikstv.no/*' => Http::response([], 200),
            'www.dw.com/graph-api/en/livestream/english' => Http::response([], 200),
        ]);

        $this->warmTv(now('Europe/Oslo'), 1, 'kongsvinger');

        $this->get('/kongsvinger/tv-utskrift')
            ->assertOk()
            ->assertSee('TV-guide Kongsvinger fengsel')
            ->assertSee('SVT1')
            ->assertSee('Kongsvinger-testprogram')
            ->assertSee('const returnUrl = "/kongsvinger";', false);

        Http::assertSent(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->url() !== 'https://www.dw.com/graph-api/en/livestream/english'
                && in_array('svt1', explode(',', $query['channels'] ?? ''), true)
                && in_array('eurosport-norge', explode(',', $query['channels'] ?? ''), true)
                && in_array('al-jazeera-english', explode(',', $query['channels'] ?? ''), true)
                && ! in_array('tv2-sport-1', explode(',', $query['channels'] ?? ''), true);
        });
    }

    private function footballListing(string $name, int $id): array
    {
        return [
            'id' => $id,
            'startsAt' => now('Europe/Oslo')->startOfWeek()->addHours(18)->toIso8601String(),
            'title' => ['type' => 'sportsTitle', 'title' => 'Premier League'],
            'sportsEvent' => ['id' => $id, 'name' => $name],
            'isLive' => true,
            'isRerun' => false,
        ];
    }
}
