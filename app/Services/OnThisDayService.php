<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use JsonException;
use RuntimeException;
use Throwable;

/** Fetches source material. Selection and presentation belong to TodayContentService. */
class OnThisDayService
{
    public function __construct(
        private ExternalDataFailureNotifier $notifier,
        private TodayDateNotificationPolicy $notificationPolicy,
    )
    {
    }

    public function forDate(CarbonInterface $date): array
    {
        $datePage = $this->norwegianDatePage($date);
        if (array_filter($datePage)) {
            return $datePage;
        }

        $payload = $this->fetch(config('services.today.wikimedia_url'), $date, 'no-onthisday');
        if (!$this->hasHistory($payload ?? [])) {
            if ($payload !== null) {
                $this->reportPayloadFailure('no-onthisday', $date);
            }
            $payload = $this->fetch(config('services.today.wikimedia_fallback_url'), $date, 'en-onthisday');
            if (!$this->hasHistory($payload ?? []) && $payload !== null) {
                $this->reportPayloadFailure('en-onthisday', $date);
            }
        }

        $payload ??= [];

        $result = [];
        foreach (['events', 'births', 'deaths'] as $group) {
            $entries = $payload[$group] ?? [];
            if ($group === 'events') {
                $entries = array_merge($payload['selected'] ?? [], $entries);
            }
            $result[$group] = $this->normalize($entries, $group !== 'events');
        }

        if (!array_filter($result)) {
            throw new RuntimeException('Wikimedia returnerte ingen brukbare oppføringer.');
        }

        return $result;
    }

    private function norwegianDatePage(CarbonInterface $date): array
    {
        if ($this->isBackedOff('no-mediawiki', $date)) {
            return [];
        }

        try {
            $month = $date->copy()->locale('nb')->translatedFormat('F');
            $title = $date->day.'. '.$month;
            $response = Http::acceptJson()->withHeaders(['User-Agent' => config('services.today.user_agent')])
                ->timeout((int) config('services.today.timeout', 5))
                ->get(config('services.today.wikipedia_api_url'), [
                    'action' => 'parse', 'page' => $title, 'prop' => 'wikitext',
                    'format' => 'json', 'formatversion' => 2,
                ]);
            $response->throw();
            $payload = $this->decodeJson($response->body());
            $wikitext = data_get($payload, 'parse.wikitext');
            if (!is_string($wikitext) || $wikitext === '') {
                throw new RuntimeException('Norsk MediaWiki mangler wikitext.');
            }

            $groups = ['events' => [], 'births' => [], 'deaths' => []];
            $section = null;
            $norwegianHistory = false;
            foreach (preg_split('/\R/u', $wikitext) as $line) {
                if (preg_match('/^==\s*([^=]+?)\s*==$/u', trim($line), $heading)) {
                    $name = mb_strtolower(trim($heading[1]));
                    $section = preg_match('/historie|hendelser|begivenheter/u', $name) ? 'events'
                        : (preg_match('/føds/u', $name) ? 'births' : (preg_match('/død/u', $name) ? 'deaths' : null));
                    $norwegianHistory = false;
                    continue;
                }
                if (preg_match('/^===\s*([^=]+?)\s*===$/u', trim($line), $heading)) {
                    $norwegianHistory = $section === 'events' && preg_match('/norsk/u', mb_strtolower($heading[1]));
                    continue;
                }
                if (!$section || !preg_match('/^\*\s*(?:\[\[)?(-?\d{1,4})(?:\]\])?\s*[–-]\s*(.+)$/u', trim($line), $match)) continue;
                $links = [];
                preg_match_all('/\[\[([^\]|#]+)(?:\|([^\]]+))?\]\]/u', $match[2], $linkMatches, PREG_SET_ORDER);
                foreach ($linkMatches as $link) {
                    $candidate = trim($link[1]);
                    if (!preg_match('/^\d+$/', $candidate) && !str_starts_with($candidate, 'Kategori:')) { $links[] = $candidate; }
                }
                if ($links === []) continue;
                $article = $links[0];
                $plain = $this->plainWikitext($match[2]);
                $groups[$section][] = [
                    'year' => (int) $match[1], 'title' => $section === 'events' ? $this->shortTitle($plain) : $this->plainWikitext($article),
                    'text' => $plain, 'description' => $plain,
                    'url' => 'https://no.wikipedia.org/wiki/'.rawurlencode(str_replace(' ', '_', $article)),
                    'wikibase_id' => null, 'article_title' => $article,
                    'norwegian_source' => true,
                    'norwegian_context' => $norwegianHistory || preg_match('/\bnorsk(?:e|t)?\b/ui', $plain) === 1,
                ];
            }

            return $this->attachWikibaseIds($groups);
        } catch (Throwable $exception) {
            $this->recordFailure('no-mediawiki', $date);
            $this->reportFailure('no-mediawiki', $exception, $date);
            return [];
        }
    }

    private function attachWikibaseIds(array $groups): array
    {
        $titles = [];
        foreach ($groups as $items) foreach ($items as $item) $titles[] = $item['article_title'];
        $ids = [];
        foreach (array_chunk(array_values(array_unique($titles)), 50) as $chunk) {
            $response = Http::acceptJson()->withHeaders(['User-Agent' => config('services.today.user_agent')])
                ->timeout((int) config('services.today.timeout', 5))
                ->get(config('services.today.wikipedia_api_url'), [
                    'action' => 'query', 'titles' => implode('|', $chunk), 'prop' => 'pageprops|info',
                    'redirects' => 1, 'format' => 'json', 'formatversion' => 2,
                ]);
            $response->throw();
            $payload = $this->decodeJson($response->body());
            $pages = data_get($payload, 'query.pages');
            if (!is_array($pages)) {
                throw new RuntimeException('Norsk MediaWiki mangler sideinformasjon.');
            }
            foreach ($pages as $page) if (!empty($page['title'])) $ids[$page['title']] = ['id' => $page['pageprops']['wikibase_item'] ?? null, 'length' => (int) ($page['length'] ?? 0)];
        }
        foreach ($groups as &$items) foreach ($items as &$item) { $metadata = $ids[$item['article_title']] ?? []; $item['wikibase_id'] = $metadata['id'] ?? null; $item['page_length'] = $metadata['length'] ?? 0; unset($item['article_title']); }
        return $groups;
    }

    private function plainWikitext(string $text): string
    {
        $text = preg_replace('/<ref\b[^>]*>.*?<\/ref>|<ref\b[^>]*\/\s*>/uis', '', $text);
        $text = preg_replace('/\{\{[^{}]*\}\}/u', '', $text);
        $text = preg_replace('/\[\[[^\]|]+\|([^\]]+)\]\]/u', '$1', $text);
        $text = preg_replace('/\[\[([^\]]+)\]\]/u', '$1', $text);
        return trim(strip_tags(str_replace(["'''", "''", '&nbsp;'], ['', '', ' '], $text)));
    }

    private function fetch(string $baseUrl, CarbonInterface $date, string $operation): ?array
    {
        if ($this->isBackedOff($operation, $date)) {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['User-Agent' => config('services.today.user_agent')])
                ->timeout((int) config('services.today.timeout', 5))
                ->get(rtrim($baseUrl, '/').sprintf('/%02d/%02d', $date->month, $date->day));
            $response->throw();

            $payload = $this->decodeJson($response->body());
            if (!array_key_exists('events', $payload) && !array_key_exists('births', $payload) && !array_key_exists('deaths', $payload)) {
                throw new RuntimeException('Wikimedia mangler forventet historikkstruktur.');
            }

            return $payload;
        } catch (Throwable $exception) {
            $this->recordFailure($operation, $date);
            $this->reportFailure($operation, $exception, $date);

            return null;
        }
    }

    private function hasHistory(array $payload): bool
    {
        return count($payload['events'] ?? []) + count($payload['births'] ?? []) + count($payload['deaths'] ?? []) > 0;
    }

    private function normalize($entries, bool $person): array
    {
        if (!is_array($entries)) {
            return [];
        }

        $items = [];
        foreach ($entries as $entry) {
            $year = filter_var($entry['year'] ?? null, FILTER_VALIDATE_INT);
            $text = trim(strip_tags((string) ($entry['text'] ?? '')));
            $page = is_array($entry['pages'][0] ?? null) ? $entry['pages'][0] : [];
            $title = trim(strip_tags((string) data_get($page, 'titles.normalized', '')));
            if ($year === false || $text === '' || ($person && $title === '')) {
                continue;
            }

            $items[] = [
                'year' => $year,
                'title' => $person ? $title : ($title ?: $this->shortTitle($text)),
                'text' => $text,
                'description' => trim(strip_tags((string) ($page['description'] ?? ''))),
                'url' => data_get($page, 'content_urls.desktop.page'),
                'wikibase_id' => $page['wikibase_item'] ?? null,
                'norwegian_source' => false,
                'norwegian_context' => false,
                'page_length' => 0,
            ];
        }

        return array_values(collect($items)->unique(fn ($item) => $item['year'].'|'.mb_strtolower($item['title']))->all());
    }

    private function shortTitle(string $text): string
    {
        $title = preg_split('/[.:–—]/u', $text, 2)[0] ?? $text;
        return mb_strlen($title) > 90 ? mb_substr($title, 0, 87).'…' : $title;
    }

    private function decodeJson(string $body): array
    {
        $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload)) {
            throw new RuntimeException('Datakilden returnerte ikke et JSON-objekt.');
        }

        return $payload;
    }

    private function reportPayloadFailure(string $operation, CarbonInterface $date): void
    {
        $this->recordFailure($operation, $date);
        $this->notifier->report('wikipedia-on-this-day', 'Wikimedia On This Day returnerte uventede data.', [
            'operation' => $operation,
            'failure_kind' => 'invalid_payload',
            'send_notification' => $this->notificationPolicy->shouldNotify($date),
            'notification_cooldown_seconds' => (int) config('services.today.failure_cache_ttl', 86400),
        ]);
    }

    private function reportFailure(string $operation, Throwable $exception, CarbonInterface $date): void
    {
        $context = [
            'operation' => $operation,
            'failure_kind' => match (true) {
                $exception instanceof ConnectionException => 'connection_failure',
                $exception instanceof RequestException => 'http_status',
                $exception instanceof JsonException => 'invalid_json',
                $exception instanceof RuntimeException => 'invalid_payload',
                default => 'unexpected_error',
            },
        ];

        if ($exception instanceof RequestException && $exception->response !== null) {
            $context['status'] = $exception->response->status();
        }

        $context['send_notification'] = $this->notificationPolicy->shouldNotify($date);
        $context['notification_cooldown_seconds'] = (int) config('services.today.failure_cache_ttl', 86400);

        $this->notifier->report('wikipedia-on-this-day', 'Wikipedia- eller Wikimedia-data kunne ikke hentes.', $context);
    }

    private function isBackedOff(string $operation, CarbonInterface $date): bool
    {
        return Cache::has($this->failureKey($operation, $date));
    }

    private function recordFailure(string $operation, CarbonInterface $date): void
    {
        Cache::put($this->failureKey($operation, $date), true, (int) config('services.today.failure_cache_ttl', 86400));
    }

    private function failureKey(string $operation, CarbonInterface $date): string
    {
        return sprintf('today.failure.wikipedia.%s.%02d-%02d', $operation, $date->month, $date->day);
    }
}
