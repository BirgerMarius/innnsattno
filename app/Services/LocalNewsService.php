<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

class LocalNewsService
{
    private const CACHE_TTL_SECONDS = 10800;
    private const STALE_CACHE_TTL_SECONDS = 604800;
    private const USER_AGENT = 'innsatt.no local-news/1.0 (+https://innsatt.no)';

    public function __construct(private ExternalDataFailureNotifier $failureNotifier)
    {
    }

    public function latest(string $prison, bool $check = false): array
    {
        $source = $this->source($prison);
        $cached = Cache::get($this->cacheKey($prison));

        if (! $check && is_array($cached) && $this->hasImageFields($cached)) {
            return $cached;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'text/html,application/xhtml+xml',
            ])
                ->connectTimeout(2)
                ->timeout(4)
                ->get($source['source_url'])
                ->throw();

            $articles = $this->parse($response->body(), $source);

            if ($articles === []) {
                throw new \RuntimeException($source['source_name'].'-siden inneholdt ingen gjenkjennelige artikler.');
            }

            Cache::put($this->cacheKey($prison), $articles, self::CACHE_TTL_SECONDS);
            Cache::put($this->staleCacheKey($prison), $articles, self::STALE_CACHE_TTL_SECONDS);
            $this->failureNotifier->reportSuccess($this->serviceName($prison), $this->operationKey($prison));
            if ($check) app(ExternalDataMonitor::class)->checked($this->serviceName($prison), $this->operationKey($prison), 'news', 'Lokale nyheter på forsiden: '.$source['source_name'], false, true, true);

            return $articles;
        } catch (Throwable $exception) {
            $this->failureNotifier->report($this->serviceName($prison), $this->failureSummary($source, $exception), [
                'operation' => $this->operationKey($prison),
                'failure_kind' => $this->failureKind($exception),
                'status' => $exception instanceof \Illuminate\Http\Client\RequestException ? $exception->response?->status() : null,
                'notification_cooldown_seconds' => 86400,
            ]);

            $stale = Cache::get($this->staleCacheKey($prison));
            if ($check) app(ExternalDataMonitor::class)->checked($this->serviceName($prison), $this->operationKey($prison), 'news', 'Lokale nyheter på forsiden: '.$source['source_name'], false, false, is_array($stale) && $stale !== [], $this->failureSummary($source, $exception));

            return is_array($stale) ? $stale : [];
        }
    }

    public function refresh(string $prison): void
    {
        $lock = Cache::lock('fetch.local-news.'.$prison, 15);
        if (! $lock->get()) return;
        try { $this->latest($prison, true); } finally { $lock->release(); }
    }

    public function cacheKey(string $prison): string
    {
        return 'local-news.'.$prison.'.latest';
    }

    public function staleCacheKey(string $prison): string
    {
        return 'local-news.'.$prison.'.stale';
    }

    public function serviceName(string $prison): string
    {
        return 'local-news-'.$prison;
    }

    public function operationKey(string $prison): string
    {
        return 'front-page-news-'.$prison;
    }

    public function parse(string $html, array $source): array
    {
        if (trim($html) === '' || !class_exists(\DOMDocument::class)) {
            return [];
        }

        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $loaded = $document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        if (! $loaded) return [];

        $xpath = new \DOMXPath($document);
        $teasers = $xpath->query('//brick-teaser-v23[@data-teaser-type="story"]');
        if ($teasers === false) return [];

        $articles = [];
        $seenUrls = [];
        foreach ($teasers as $teaser) {
            $linkNode = $xpath->query('.//a[@itemprop="url"]', $teaser)->item(0);
            $titleNode = $xpath->query('.//*[@itemprop="titleText"]', $teaser)->item(0);
            if (! $linkNode || ! $titleNode) continue;

            $url = $this->normalizeUrl($linkNode->getAttribute('href'), $source);
            $title = trim(preg_replace('/\s+/u', ' ', $titleNode->textContent) ?? '');
            if ($url === null || $title === '' || isset($seenUrls[$url])) continue;

            $contentModel = $xpath->query('.//meta[@itemprop="contentModel"]', $teaser)->item(0);
            $dateNode = $xpath->query('.//time[@datetime] | .//*[@itemprop="datePublished"][@datetime]', $teaser)->item(0);
            $articles[] = [
                'title' => $title,
                'url' => $url,
                'published_at' => $this->parseDate($dateNode?->getAttribute('datetime')),
                'is_subscription' => $teaser->getAttribute('data-premium') === 'true' || strtolower($contentModel?->getAttribute('content') ?? '') === 'paywall',
                'image_url' => $this->imageUrl($xpath, $teaser, $source),
            ];
            $seenUrls[$url] = true;
            if (count($articles) === 6) break;
        }

        return $articles;
    }

    private function source(string $prison): array
    {
        $source = config('prisons.all.'.$prison.'.local_news');
        if (! is_array($source) || !is_string($source['source_name'] ?? null) || !is_string($source['source_url'] ?? null) || !is_array($source['article_hosts'] ?? null)) {
            throw new InvalidArgumentException('Ugyldig lokal nyhetskilde for fengsel: '.$prison);
        }
        return $source;
    }

    private function normalizeUrl(string $url, array $source): ?string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($url === '') return null;
        $base = parse_url($source['source_url']);
        $baseHost = $base['host'] ?? '';
        if (str_starts_with($url, '/')) $url = str_starts_with($url, '//') ? 'https:'.$url : 'https://'.$baseHost.$url;
        $parts = parse_url($url);
        if (!is_array($parts)) return null;
        $host = strtolower($parts['host'] ?? '');
        if (!in_array($host, $source['article_hosts'], true) || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) return null;
        return 'https://'.$host.'/'.ltrim($parts['path'] ?? '/', '/');
    }

    private function imageUrl(\DOMXPath $xpath, \DOMElement $teaser, array $source): ?string
    {
        $imageNode = $xpath->query('.//*[@itemprop="teaser_image"]//*[@data-src or @data-srcset or @data-original or @src or @srcset] | .//img | .//source[@srcset]', $teaser)->item(0);
        if (! $imageNode) return null;
        foreach (['data-src', 'data-srcset', 'data-original', 'src', 'srcset'] as $attribute) {
            $value = trim($imageNode->getAttribute($attribute));
            if ($value === '') continue;
            if (in_array($attribute, ['data-srcset', 'srcset'], true)) $value = trim(preg_split('/\s+/', trim(explode(',', $value)[0]))[0] ?? '');
            $url = $this->normalizeImageUrl($value, $source);
            if ($url !== null) return $url;
        }
        return null;
    }

    private function hasImageFields(array $articles): bool
    {
        foreach ($articles as $article) if (!is_array($article) || !array_key_exists('image_url', $article)) return false;
        return true;
    }

    private function normalizeImageUrl(string $url, array $source): ?string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (str_starts_with($url, '//')) $url = 'https:'.$url;
        if (str_starts_with($url, '/')) $url = 'https://'.parse_url($source['source_url'], PHP_URL_HOST).$url;
        $parts = parse_url($url);
        if (!is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) return null;
        return $url;
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date) return null;
        try { return Carbon::parse($date)->locale('nb')->translatedFormat('j. M Y'); } catch (Throwable) { return null; }
    }

    private function failureKind(Throwable $exception): string
    {
        if ($exception instanceof \Illuminate\Http\Client\ConnectionException) return 'connection_exception';
        if ($exception instanceof \Illuminate\Http\Client\RequestException) return 'http_status';
        return 'invalid_payload';
    }

    private function failureSummary(array $source, Throwable $exception): string
    {
        if ($exception instanceof \Illuminate\Http\Client\RequestException) return $source['source_name'].' svarte med HTTP-status '.$exception->response?->status().'.';
        if ($exception instanceof \Illuminate\Http\Client\ConnectionException) return $source['source_name'].' kunne ikke nås.';
        return $source['source_name'].' returnerte ingen gjenkjennelige artikler.';
    }
}
