<?php

namespace Tests\Unit;

use App\Services\LocalNewsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocalNewsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function testItUsesTheConfiguredSourceAndKeepsRingerikeCompatibility(): void
    {
        Http::fake([
            'https://www.ringblad.no/ringerike-fengsel/' => Http::response($this->html($this->teaser('/sak/s/5-45-100', 'Ringerike-sak', true, '2026-07-29T12:00:00+02:00', '/teaser.jpg')), 200),
        ]);

        $articles = app(LocalNewsService::class)->latest('ringerike');

        $this->assertSame('Ringerike-sak', $articles[0]['title']);
        $this->assertSame('https://www.ringblad.no/sak/s/5-45-100', $articles[0]['url']);
        $this->assertTrue($articles[0]['is_subscription']);
        $this->assertSame('29. jul 2026', $articles[0]['published_at']);
        $this->assertSame('https://www.ringblad.no/teaser.jpg', $articles[0]['image_url']);
        Http::assertSent(fn ($request) => $request->url() === 'https://www.ringblad.no/ringerike-fengsel/');
    }

    public function testItSelectsEachPrisonsConfiguredSourceAndOnlyAcceptsItsArticleHosts(): void
    {
        Http::fake([
            'https://www.h-a.no/ilseng-fengsel/' => Http::response($this->html(
                $this->teaser('/ilseng/s/5-139-1', 'Ilseng-sak').$this->teaser('https://www.glomdalen.no/feil/s/80-19-1', 'Feil vert')
            ), 200),
            'https://www.glomdalen.no/kongsvinger-fengsel/' => Http::response($this->html(
                $this->teaser('/kongsvinger/s/80-19-1', 'Kongsvinger-sak').$this->teaser('https://user@www.glomdalen.no/feil', 'Brukernavn')
            ), 200),
            'https://www.glomdalen.no/tag/kriminalomsorgen-innlandet' => Http::response($this->html($this->teaser('/tag/s/80-19-2', 'Innlandet-sak')), 200),
        ]);

        $service = app(LocalNewsService::class);
        $ilseng = $service->latest('ilseng');
        $kongsvinger = $service->latest('kongsvinger');

        $this->assertSame(['Ilseng-sak'], array_column($ilseng, 'title'));
        $this->assertSame('https://www.h-a.no/ilseng/s/5-139-1', $ilseng[0]['url']);
        $this->assertSame(['Kongsvinger-sak', 'Innlandet-sak'], array_column($kongsvinger, 'title'));
        $this->assertSame('https://www.glomdalen.no/kongsvinger/s/80-19-1', $kongsvinger[0]['url']);
    }

    public function testKongsvingerMergesDeduplicatesAndSortsArticlesByPublishedDate(): void
    {
        Http::fake([
            'https://www.glomdalen.no/kongsvinger-fengsel/' => Http::response($this->html(
                $this->teaser('/eldre/s/80-19-1', 'Eldre sak', false, '2026-01-01T12:00:00+01:00')
                .$this->teaser('/delt/s/80-19-2?utm_source=tag', 'Delt sak', false, '2026-01-03T12:00:00+01:00')
            ), 200),
            'https://www.glomdalen.no/tag/kriminalomsorgen-innlandet' => Http::response($this->html(
                $this->teaser('/nyere/s/80-19-3', 'Nyere sak', false, '2026-01-04T12:00:00+01:00')
                .$this->teaser('/delt/s/80-19-2#kommentarer', 'Delt sak fra tagg', false, '2026-01-03T12:00:00+01:00')
            ), 200),
        ]);

        $articles = app(LocalNewsService::class)->latest('kongsvinger');

        $this->assertSame(['Nyere sak', 'Delt sak', 'Eldre sak'], array_column($articles, 'title'));
        $this->assertCount(3, $articles);
        $this->assertArrayNotHasKey('sort_published_at', $articles[0]);
    }

    public function testAFailedKongsvingerEntryDoesNotHideArticlesFromTheOtherEntry(): void
    {
        Http::fake([
            'https://www.glomdalen.no/kongsvinger-fengsel/' => Http::response('', 503),
            'https://www.glomdalen.no/tag/kriminalomsorgen-innlandet' => Http::response($this->html(
                $this->teaser('/tag/s/80-19-4', 'Sak fra fungerende tagg')
            ), 200),
        ]);

        $articles = app(LocalNewsService::class)->latest('kongsvinger');

        $this->assertSame(['Sak fra fungerende tagg'], array_column($articles, 'title'));
    }

    public function testCachesStaleArticlesAndFailuresSeparatelyPerPrison(): void
    {
        $service = app(LocalNewsService::class);
        $ilsengStale = [['title' => 'Ilseng reserve', 'url' => 'https://www.h-a.no/ilseng/s/5-139-2', 'published_at' => null, 'is_subscription' => false, 'image_url' => null]];
        $kongsvingerStale = [['title' => 'Kongsvinger reserve', 'url' => 'https://www.glomdalen.no/kongsvinger/s/80-19-2', 'published_at' => null, 'is_subscription' => false, 'image_url' => null]];
        Cache::put($service->staleCacheKey('ilseng'), $ilsengStale, 60);
        Cache::put($service->staleCacheKey('kongsvinger'), $kongsvingerStale, 60);
        Http::fake(fn () => throw new ConnectionException('Timed out'));

        $this->assertSame($ilsengStale, $service->latest('ilseng'));
        $this->assertSame($kongsvingerStale, $service->latest('kongsvinger'));
        $this->assertNotSame($service->cacheKey('ilseng'), $service->cacheKey('kongsvinger'));
        $this->assertNotSame($service->staleCacheKey('ilseng'), $service->staleCacheKey('kongsvinger'));
        $this->assertNotSame($service->serviceName('ilseng'), $service->serviceName('kongsvinger'));
        $this->assertNotSame($service->operationKey('ilseng'), $service->operationKey('kongsvinger'));
    }

    private function html(string $teasers): string
    {
        return '<html><body>'.$teasers.'</body></html>';
    }

    private function teaser(string $url, string $title, bool $premium = false, ?string $publishedAt = null, ?string $imageUrl = null): string
    {
        return '<brick-teaser-v23 data-teaser-type="story" data-premium="'.($premium ? 'true' : 'false').'">'
            .'<meta itemprop="contentModel" content="'.($premium ? 'paywall' : 'open').'">'
            .'<a itemprop="url" href="'.$url.'"><span itemprop="titleText">'.$title.'</span></a>'
            .($publishedAt ? '<time datetime="'.$publishedAt.'"></time>' : '')
            .($imageUrl ? '<img src="'.$imageUrl.'" alt="">' : '')
            .'</brick-teaser-v23>';
    }
}
