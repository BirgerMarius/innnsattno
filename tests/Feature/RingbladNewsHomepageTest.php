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
            'image_url' => 'https://images.ringblad.no/nyhet.jpg',
        ]], 60);

        $this->get(route('tv'))
            ->assertOk()
            ->assertSee('Lokale nyheter')
            ->assertSee('Fra Ringerikes Blad')
            ->assertSee('Nyhet om Ringerike fengsel')
            ->assertSee('class="local-news-section"', false)
            ->assertSee('class="local-news-card local-news-card--image"', false)
            ->assertSee('src="https://images.ringblad.no/nyhet.jpg"', false)
            ->assertSee('Se flere lokale nyheter hos Ringerikes Blad')
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

    public function testHomepageSplitsTheSixNewestArticlesIntoImageAndTextRows(): void
    {
        $articles = [];

        for ($number = 1; $number <= 6; $number++) {
            $articles[] = [
                'title' => 'Nyhet '.$number,
                'url' => 'https://www.ringblad.no/nyhet/s/5-45-50'.$number,
                'published_at' => '20. aug 2026',
                'is_subscription' => $number === 4,
                'image_url' => 'https://images.ringblad.no/nyhet-'.$number.'.jpg',
            ];
        }

        Cache::put(RingbladNewsService::CACHE_KEY, $articles, 60);

        $response = $this->get(route('tv'))
            ->assertOk()
            ->assertSeeInOrder(['Nyhet 1', 'Nyhet 2', 'Nyhet 3', 'Nyhet 4', 'Nyhet 5', 'Nyhet 6'])
            ->assertSee('class="local-news-card local-news-card--text"', false)
            ->assertDontSee('local-news-card-image--missing', false)
            ->assertSee('src="https://images.ringblad.no/nyhet-1.jpg"', false)
            ->assertSee('src="https://images.ringblad.no/nyhet-2.jpg"', false)
            ->assertSee('src="https://images.ringblad.no/nyhet-3.jpg"', false)
            ->assertDontSee('src="https://images.ringblad.no/nyhet-4.jpg"', false)
            ->assertDontSee('src="https://images.ringblad.no/nyhet-5.jpg"', false)
            ->assertDontSee('src="https://images.ringblad.no/nyhet-6.jpg"', false)
            ->assertSee('20. aug 2026')
            ->assertSee('Abonnement');

        $content = (string) $response->getContent();
        $this->assertSame(3, substr_count($content, 'class="local-news-card local-news-card--image"'));
        $this->assertSame(3, substr_count($content, 'class="local-news-card local-news-card--text"'));
    }
}
