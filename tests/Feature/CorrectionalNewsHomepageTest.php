<?php

namespace Tests\Feature;

use App\CorrectionalNewsCluster;
use App\CorrectionalNewsItem;
use App\Services\RingbladNewsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CorrectionalNewsHomepageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function testHomepageKeepsHighSignificanceUnionNewsOutOfAnEmptyNationalGroup(): void
    {
        Cache::put(RingbladNewsService::CACHE_KEY, [[
            'title' => 'Lokal sak om Ringerike fengsel',
            'url' => 'https://www.ringblad.no/lokal/s/5-45-1',
            'published_at' => '16. sep 2026',
            'is_subscription' => false,
            'image_url' => null,
        ]], 60);

        $this->unionStory('nff', 'Fengslene må spare 83 millioner', 80);
        $this->unionStory('ky', 'KY-sak om bemanning');

        $this->get(route('tv'))
            ->assertOk()
            ->assertSee('Aktuelt fra kriminalomsorgen')
            ->assertSee('Lokalt')
            ->assertSee('Lokal sak om Ringerike fengsel')
            ->assertSee('Fra fagforeningene')
            ->assertSee('Fengslene må spare 83 millioner')
            ->assertSee('KY-sak om bemanning')
            ->assertDontSee('>Nasjonalt<', false)
            ->assertDontSee('local-news-grid--text', false);
    }

    private function unionStory(string $sourceKey, string $title, int $nationalSignificanceScore = 0): void
    {
        $now = now();
        $cluster = CorrectionalNewsCluster::create([
            'category' => 'union', 'display_title' => $title, 'primary_source' => strtoupper($sourceKey),
            'primary_url' => 'https://example.test/'.$sourceKey.'/'.str_replace(' ', '-', $title),
            'primary_published_at' => $now, 'relevance_score' => 100,
            'national_significance_score' => $nationalSignificanceScore, 'source_count' => 1,
            'first_seen_at' => $now, 'last_seen_at' => $now, 'expires_at' => $now->copy()->addDays(30),
        ]);
        CorrectionalNewsItem::create([
            'correctional_news_cluster_id' => $cluster->id, 'source_key' => $sourceKey, 'source_name' => strtoupper($sourceKey),
            'original_title' => $title, 'normalized_title' => mb_strtolower($title),
            'original_url' => $cluster->primary_url, 'normalized_url' => $cluster->primary_url,
            'normalized_url_hash' => hash('sha256', $cluster->primary_url), 'published_at' => $now, 'fetched_at' => $now,
            'relevance_score' => 100, 'national_significance_score' => $nationalSignificanceScore,
            'labels' => [],
        ]);
    }
}
