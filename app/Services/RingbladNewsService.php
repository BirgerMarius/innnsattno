<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RingbladNewsService
{
    public const SOURCE_URL = 'https://www.ringblad.no/ringerike-fengsel/';
    public const CACHE_KEY = 'ringblad.ringerike-fengsel.latest';
    public const STALE_CACHE_KEY = 'ringblad.ringerike-fengsel.stale';

    private const CACHE_TTL_SECONDS = 10800;
    private const STALE_CACHE_TTL_SECONDS = 604800;
    private const USER_AGENT = 'innsatt.no local-news/1.0 (+https://innsatt.no)';

    public function latest(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'text/html,application/xhtml+xml',
            ])
                ->connectTimeout(2)
                ->timeout(4)
                ->get(self::SOURCE_URL)
                ->throw();

            $articles = $this->parse($response->body());

            if ($articles === []) {
                throw new \RuntimeException('Ringblad page contained no recognized articles.');
            }

            Cache::put(self::CACHE_KEY, $articles, self::CACHE_TTL_SECONDS);
            Cache::put(self::STALE_CACHE_KEY, $articles, self::STALE_CACHE_TTL_SECONDS);

            return $articles;
        } catch (Throwable $exception) {
            Log::warning('Could not refresh Ringblad local news.', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            $stale = Cache::get(self::STALE_CACHE_KEY);

            return is_array($stale) ? $stale : [];
        }
    }

    public function parse(string $html): array
    {
        if (trim($html) === '' || !class_exists(\DOMDocument::class)) {
            return [];
        }

        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $loaded = $document->loadHTML(
            '<?xml encoding="utf-8" ?>'.$html,
            LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        if (!$loaded) {
            return [];
        }

        $xpath = new \DOMXPath($document);
        $teasers = $xpath->query(
            '//brick-teaser-v23[@data-teaser-type="story"]'
        );

        if ($teasers === false) {
            return [];
        }

        $articles = [];
        $seenUrls = [];

        foreach ($teasers as $teaser) {
            $linkNode = $xpath->query('.//a[@itemprop="url"]', $teaser)->item(0);
            $titleNode = $xpath->query('.//*[@itemprop="titleText"]', $teaser)->item(0);

            if (!$linkNode || !$titleNode) {
                continue;
            }

            $url = $this->normalizeUrl($linkNode->getAttribute('href'));
            $title = trim(preg_replace('/\s+/u', ' ', $titleNode->textContent) ?? '');

            if ($url === null || $title === '' || isset($seenUrls[$url])) {
                continue;
            }

            $contentModel = $xpath->query(
                './/meta[@itemprop="contentModel"]',
                $teaser
            )->item(0);
            $dateNode = $xpath->query(
                './/time[@datetime] | .//*[@itemprop="datePublished"][@datetime]',
                $teaser
            )->item(0);

            $articles[] = [
                'title' => $title,
                'url' => $url,
                'published_at' => $this->parseDate($dateNode?->getAttribute('datetime')),
                'is_subscription' => $teaser->getAttribute('data-premium') === 'true'
                    || strtolower($contentModel?->getAttribute('content') ?? '') === 'paywall',
            ];
            $seenUrls[$url] = true;

            if (count($articles) === 5) {
                break;
            }
        }

        return $articles;
    }

    private function normalizeUrl(string $url): ?string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/')) {
            $url = str_starts_with($url, '//')
                ? 'https:'.$url
                : 'https://www.ringblad.no'.$url;
        }

        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');

        if (
            !in_array($host, ['ringblad.no', 'www.ringblad.no'], true)
            || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['port'])
        ) {
            return null;
        }

        $path = '/'.ltrim($parts['path'] ?? '/', '/');

        return 'https://www.ringblad.no'.$path;
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        try {
            return Carbon::parse($date)->locale('nb')->translatedFormat('j. M Y');
        } catch (Throwable) {
            return null;
        }
    }
}
