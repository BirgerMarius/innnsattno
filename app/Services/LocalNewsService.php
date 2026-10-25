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

    public function __construct(private ExternalDataFailureNotifier $failureNotifier) {}

    public function latest(string $prison, bool $check = false): array
    {
        $cached = Cache::get($this->cacheKey($prison));
        if (! $check && is_array($cached) && $this->hasImageFields($cached)) return $cached;
        return $this->fetch($prison, $check);
    }

    public function refresh(string $prison): void
    {
        $lock = Cache::lock('fetch.local-news.'.$prison, 15);
        if (! $lock->get()) return;
        try { $this->fetch($prison, true); } finally { $lock->release(); }
    }

    public function needsRefresh(string $prison, callable $due): bool
    {
        foreach (array_keys($this->sources($prison)) as $sourceIndex) {
            if ($due($this->serviceName($prison, $sourceIndex), $this->operationKey($prison, $sourceIndex))) return true;
        }
        return false;
    }

    public function cacheKey(string $prison): string { return 'local-news.'.$prison.'.latest'; }
    public function staleCacheKey(string $prison): string { return 'local-news.'.$prison.'.stale'; }
    public function serviceName(string $prison, int $sourceIndex = 0): string { return 'local-news-'.$prison.'-source-'.$sourceIndex; }
    public function operationKey(string $prison, int $sourceIndex = 0): string { return 'front-page-news-'.$prison.'-source-'.$sourceIndex; }

    public function parse(string $html, array $source): array
    {
        if (trim($html) === '' || !class_exists(\DOMDocument::class)) return [];
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
            $publishedAt = $this->publishedAt($dateNode?->getAttribute('datetime'));
            $articles[] = [
                'title' => $title, 'url' => $url, 'published_at' => $publishedAt['label'],
                'sort_published_at' => $publishedAt['timestamp'],
                'is_subscription' => $teaser->getAttribute('data-premium') === 'true' || strtolower($contentModel?->getAttribute('content') ?? '') === 'paywall',
                'image_url' => $this->imageUrl($xpath, $teaser, $source),
            ];
            $seenUrls[$url] = true;
            if (count($articles) === 6) break;
        }
        return $articles;
    }

    private function fetch(string $prison, bool $check): array
    {
        $news = $this->newsConfig($prison);
        $articles = [];
        $seenUrls = [];
        $sourceOrder = 0;
        foreach ($this->sources($prison) as $sourceIndex => $source) {
            try {
                $response = Http::withHeaders(['User-Agent' => self::USER_AGENT, 'Accept' => 'text/html,application/xhtml+xml'])
                    ->connectTimeout(2)->timeout(4)->get($source['url'])->throw();
                $parsed = $this->parse($response->body(), $source);
                if ($parsed === []) throw new \RuntimeException($news['source_name'].'-siden inneholdt ingen gjenkjennelige artikler.');
                $this->failureNotifier->reportSuccess($this->serviceName($prison, $sourceIndex), $this->operationKey($prison, $sourceIndex));
                if ($check) app(ExternalDataMonitor::class)->checked($this->serviceName($prison, $sourceIndex), $this->operationKey($prison, $sourceIndex), 'news', $this->description($news, $sourceIndex), false, true, true);
                foreach ($parsed as $article) {
                    if (isset($seenUrls[$article['url']])) continue;
                    $article['sort_order'] = $sourceOrder++;
                    $articles[] = $article;
                    $seenUrls[$article['url']] = true;
                }
            } catch (Throwable $exception) {
                $this->failureNotifier->report($this->serviceName($prison, $sourceIndex), $this->failureSummary($news, $exception), [
                    'operation' => $this->operationKey($prison, $sourceIndex), 'failure_kind' => $this->failureKind($exception),
                    'status' => $exception instanceof \Illuminate\Http\Client\RequestException ? $exception->response?->status() : null,
                    'notification_cooldown_seconds' => 86400,
                ]);
                if ($check) app(ExternalDataMonitor::class)->checked($this->serviceName($prison, $sourceIndex), $this->operationKey($prison, $sourceIndex), 'news', $this->description($news, $sourceIndex), false, false, false, $this->failureSummary($news, $exception));
            }
        }
        if ($articles === []) return $this->stale($prison);
        usort($articles, fn (array $left, array $right) => ($right['sort_published_at'] <=> $left['sort_published_at']) ?: ($left['sort_order'] <=> $right['sort_order']));
        foreach ($articles as &$article) unset($article['sort_published_at'], $article['sort_order']);
        unset($article);
        Cache::put($this->cacheKey($prison), $articles, self::CACHE_TTL_SECONDS);
        Cache::put($this->staleCacheKey($prison), $articles, self::STALE_CACHE_TTL_SECONDS);
        return $articles;
    }

    private function stale(string $prison): array
    {
        $stale = Cache::get($this->staleCacheKey($prison));
        return is_array($stale) ? $stale : [];
    }

    private function newsConfig(string $prison): array
    {
        $news = config('prisons.all.'.$prison.'.local_news');
        if (!is_array($news) || !is_string($news['source_name'] ?? null) || !is_array($news['sources'] ?? null)) throw new InvalidArgumentException('Ugyldig lokal nyhetskilde for fengsel: '.$prison);
        return $news;
    }

    private function sources(string $prison): array
    {
        $sources = $this->newsConfig($prison)['sources'];
        foreach ($sources as $source) if (!is_array($source) || !is_string($source['url'] ?? null) || !is_array($source['article_hosts'] ?? null)) throw new InvalidArgumentException('Ugyldig lokal nyhetsinngang for fengsel: '.$prison);
        return $sources;
    }

    private function description(array $news, int $sourceIndex): string { return 'Lokale nyheter på forsiden: '.$news['source_name'].' (inngang '.($sourceIndex + 1).')'; }

    private function normalizeUrl(string $url, array $source): ?string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($url === '') return null;
        $baseHost = parse_url($source['url'], PHP_URL_HOST);
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

    private function normalizeImageUrl(string $url, array $source): ?string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (str_starts_with($url, '//')) $url = 'https:'.$url;
        if (str_starts_with($url, '/')) $url = 'https://'.parse_url($source['url'], PHP_URL_HOST).$url;
        $parts = parse_url($url);
        if (!is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) return null;
        return $url;
    }

    private function hasImageFields(array $articles): bool
    {
        foreach ($articles as $article) if (!is_array($article) || !array_key_exists('image_url', $article)) return false;
        return true;
    }

    private function publishedAt(?string $date): array
    {
        if (!$date) return ['label' => null, 'timestamp' => 0];
        try {
            $parsed = Carbon::parse($date);
            return ['label' => $parsed->locale('nb')->translatedFormat('j. M Y'), 'timestamp' => $parsed->getTimestamp()];
        } catch (Throwable) { return ['label' => null, 'timestamp' => 0]; }
    }

    private function failureKind(Throwable $exception): string
    {
        if ($exception instanceof \Illuminate\Http\Client\ConnectionException) return 'connection_exception';
        if ($exception instanceof \Illuminate\Http\Client\RequestException) return 'http_status';
        return 'invalid_payload';
    }

    private function failureSummary(array $news, Throwable $exception): string
    {
        if ($exception instanceof \Illuminate\Http\Client\RequestException) return $news['source_name'].' svarte med HTTP-status '.$exception->response?->status().'.';
        if ($exception instanceof \Illuminate\Http\Client\ConnectionException) return $news['source_name'].' kunne ikke nås.';
        return $news['source_name'].' returnerte ingen gjenkjennelige artikler.';
    }
}
