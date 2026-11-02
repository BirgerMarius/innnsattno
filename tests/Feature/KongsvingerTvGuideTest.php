<?php

namespace Tests\Feature;

use App\Services\TvGuideService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KongsvingerTvGuideTest extends TestCase
{
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
}
