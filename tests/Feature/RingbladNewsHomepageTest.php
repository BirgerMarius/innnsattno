<?php

namespace Tests\Feature;

use App\Services\RingbladNewsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RingbladNewsHomepageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(RingbladNewsService::CACHE_KEY);
        Cache::forget(RingbladNewsService::STALE_CACHE_KEY);
    }

    public function testHomepageShowsLocalNewsWithSafeExternalLinks(): void
    {
        Cache::put(RingbladNewsService::CACHE_KEY, [[
            'title' => 'Nyhet om Ringerike fengsel',
            'url' => 'https://www.ringblad.no/nyhet/s/5-45-500',
            'published_at' => null,
            'is_subscription' => true,
        ]], 60);

        $this->get(route('tv'))
            ->assertOk()
            ->assertSee('Lokale nyheter')
            ->assertSee('Nyhet om Ringerike fengsel')
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertSee('Abonnement');
    }

    public function testExternalFailureDoesNotBreakHomepage(): void
    {
        Http::fake([
            RingbladNewsService::SOURCE_URL => Http::response('Unavailable', 503),
        ]);

        $this->get(route('tv'))
            ->assertOk()
            ->assertSee('Skriv ut TV-guide – Ringerike fengsel')
            ->assertDontSee('local-news-column');
    }
}
