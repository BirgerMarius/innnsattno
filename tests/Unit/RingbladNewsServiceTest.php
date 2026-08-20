<?php

namespace Tests\Unit;

use App\Services\RingbladNewsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RingbladNewsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(RingbladNewsService::CACHE_KEY);
        Cache::forget(RingbladNewsService::STALE_CACHE_KEY);
    }

    public function testItFetchesAndParsesPublicArticleDetails(): void
    {
        Http::fake([
            RingbladNewsService::SOURCE_URL => Http::response($this->html([
                $this->teaser('/sak-en/s/5-45-100', 'Første sak', true, '2026-07-29T12:00:00+02:00', 'https://images.ringblad.no/first.jpg'),
                $this->teaser('/sak-to/s/5-45-101', 'Andre sak'),
            ]), 200),
        ]);

        $articles = app(RingbladNewsService::class)->latest();

        $this->assertCount(2, $articles);
        $this->assertSame('Første sak', $articles[0]['title']);
        $this->assertSame('https://www.ringblad.no/sak-en/s/5-45-100', $articles[0]['url']);
        $this->assertSame('29. jul 2026', $articles[0]['published_at']);
        $this->assertTrue($articles[0]['is_subscription']);
        $this->assertSame('https://images.ringblad.no/first.jpg', $articles[0]['image_url']);
        $this->assertFalse($articles[1]['is_subscription']);
        $this->assertNull($articles[1]['image_url']);
        Http::assertSentCount(1);

        app(RingbladNewsService::class)->latest();
        Http::assertSentCount(1);
    }

    public function testItNormalizesLinksAndRemovesDuplicates(): void
    {
        $service = app(RingbladNewsService::class);

        $articles = $service->parse($this->html([
            $this->teaser('/samme-sak/s/5-45-200?utm_source=test', 'Samme sak'),
            $this->teaser('https://ringblad.no/samme-sak/s/5-45-200#kommentarer', 'Duplikat'),
            $this->teaser('//www.ringblad.no/annen-sak/s/5-45-201', 'Annen sak'),
        ]));

        $this->assertCount(2, $articles);
        $this->assertSame('https://www.ringblad.no/samme-sak/s/5-45-200', $articles[0]['url']);
        $this->assertSame('https://www.ringblad.no/annen-sak/s/5-45-201', $articles[1]['url']);
    }

    public function testItOnlyAcceptsExpectedRingbladHosts(): void
    {
        $articles = app(RingbladNewsService::class)->parse($this->html([
            $this->teaser('https://evil.example/sak/s/5-45-300', 'Feil vert'),
            $this->teaser('https://ringblad.no.evil.example/sak/s/5-45-301', 'Lur vert'),
            $this->teaser('javascript:alert(1)', 'Script'),
            $this->teaser('https://user@ringblad.no/sak/s/5-45-302', 'Brukernavn'),
            $this->teaser('https://www.ringblad.no/gyldig/s/5-45-303', 'Gyldig'),
        ]));

        $this->assertCount(1, $articles);
        $this->assertSame('Gyldig', $articles[0]['title']);
    }

    public function testItUsesStaleCacheWhenTheRequestTimesOut(): void
    {
        $stale = [[
            'title' => 'Sist lagrede sak',
            'url' => 'https://www.ringblad.no/sist-lagret/s/5-45-400',
            'published_at' => null,
            'is_subscription' => false,
        ]];
        Cache::put(RingbladNewsService::STALE_CACHE_KEY, $stale, 60);
        Http::fake(fn () => throw new ConnectionException('Timed out'));

        $this->assertSame($stale, app(RingbladNewsService::class)->latest());
    }

    public function testItUsesAnImageFromTheExistingTeaserMarkupWhenAvailable(): void
    {
        $articles = app(RingbladNewsService::class)->parse($this->html([
            $this->ringbladTeaserWithImage(
                '/bilde/s/5-45-500',
                'Sak med bilde',
                '//g.acdn.no/obscura/API/dynamic/r1/ece5/article.jpg?chk=ABC123'
            ),
            $this->teaser('/uten-bilde/s/5-45-501', 'Sak uten bilde'),
        ]));

        $this->assertSame(
            'https://g.acdn.no/obscura/API/dynamic/r1/ece5/article.jpg?chk=ABC123',
            $articles[0]['image_url']
        );
        $this->assertNull($articles[1]['image_url']);
    }

    public function testItRefreshesCachedArticlesCreatedBeforeImageUrlsWereAdded(): void
    {
        Cache::put(RingbladNewsService::CACHE_KEY, [[
            'title' => 'Gammel cachet sak',
            'url' => 'https://www.ringblad.no/gammel/s/5-45-502',
            'published_at' => null,
            'is_subscription' => false,
        ]], 60);
        Http::fake([
            RingbladNewsService::SOURCE_URL => Http::response($this->html([
                $this->ringbladTeaserWithImage(
                    '/oppdatert/s/5-45-503',
                    'Oppdatert sak',
                    'https://g.acdn.no/obscura/API/dynamic/r1/ece5/updated.jpg'
                ),
            ]), 200),
        ]);

        $articles = app(RingbladNewsService::class)->latest();

        $this->assertSame('Oppdatert sak', $articles[0]['title']);
        $this->assertSame('https://g.acdn.no/obscura/API/dynamic/r1/ece5/updated.jpg', $articles[0]['image_url']);
        Http::assertSentCount(1);
    }

    private function html(array $teasers): string
    {
        return '<html><body><section class="tag-page"><div class="tag-pages--article-list">'
            .implode('', $teasers)
            .'</div></section></body></html>';
    }

    private function teaser(
        string $url,
        string $title,
        bool $premium = false,
        ?string $publishedAt = null,
        ?string $imageUrl = null
    ): string {
        return sprintf(
            '<brick-teaser-v23 data-teaser-type="story" data-premium="%s">'
            .'<meta itemprop="contentModel" content="%s">'
            .'<a itemprop="url" href="%s"><span itemprop="titleText">%s</span>%s</a>'
            .'%s'
            .'</brick-teaser-v23>',
            $premium ? 'true' : 'false',
            $premium ? 'paywall' : 'open',
            htmlspecialchars($url, ENT_QUOTES),
            htmlspecialchars($title, ENT_QUOTES),
            $publishedAt
                ? '<time datetime="'.htmlspecialchars($publishedAt, ENT_QUOTES).'"></time>'
                : '',
            $imageUrl
                ? '<img data-src="'.htmlspecialchars($imageUrl, ENT_QUOTES).'" alt="">'
                : ''
        );
    }

    private function ringbladTeaserWithImage(string $url, string $title, string $imageUrl): string
    {
        return sprintf(
            '<brick-teaser-v23 data-teaser-type="story" data-premium="false">'
            .'<a itemprop="url" href="%s"><span itemprop="titleText">%s</span></a>'
            .'<div itemprop="teaser_image"><brick-image-v6 data-src="%s" '
            .'data-srcset="%s 180w, %s 480w"></brick-image-v6></div>'
            .'</brick-teaser-v23>',
            htmlspecialchars($url, ENT_QUOTES),
            htmlspecialchars($title, ENT_QUOTES),
            htmlspecialchars($imageUrl, ENT_QUOTES),
            htmlspecialchars($imageUrl, ENT_QUOTES),
            htmlspecialchars($imageUrl, ENT_QUOTES)
        );
    }
}
