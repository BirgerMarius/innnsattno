<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Fetches source material. Selection and presentation belong to TodayContentService. */
class OnThisDayService
{
    public function forDate(CarbonInterface $date): array
    {
        $datePage = $this->norwegianDatePage($date);
        if (array_filter($datePage)) {
            return $datePage;
        }

        $payload = $this->fetch(config('services.today.wikimedia_url'), $date);
        if (!$this->hasHistory($payload)) {
            $payload = $this->fetch(config('services.today.wikimedia_fallback_url'), $date);
        }

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
        try {
            $month = $date->copy()->locale('nb')->translatedFormat('F');
            $title = $date->day.'. '.$month;
            $wikitext = Http::acceptJson()->withHeaders(['User-Agent' => config('services.today.user_agent')])
                ->timeout((int) config('services.today.timeout', 5))
                ->get(config('services.today.wikipedia_api_url'), [
                    'action' => 'parse', 'page' => $title, 'prop' => 'wikitext',
                    'format' => 'json', 'formatversion' => 2,
                ])->throw()->json('parse.wikitext', '');
            if (!is_string($wikitext) || $wikitext === '') return [];

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
        } catch (\Throwable $exception) {
            report($exception);
            return [];
        }
    }

    private function attachWikibaseIds(array $groups): array
    {
        $titles = [];
        foreach ($groups as $items) foreach ($items as $item) $titles[] = $item['article_title'];
        $ids = [];
        foreach (array_chunk(array_values(array_unique($titles)), 50) as $chunk) {
            $pages = Http::acceptJson()->withHeaders(['User-Agent' => config('services.today.user_agent')])
                ->timeout((int) config('services.today.timeout', 5))
                ->get(config('services.today.wikipedia_api_url'), [
                    'action' => 'query', 'titles' => implode('|', $chunk), 'prop' => 'pageprops|info',
                    'redirects' => 1, 'format' => 'json', 'formatversion' => 2,
                ])->throw()->json('query.pages', []);
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

    private function fetch(string $baseUrl, CarbonInterface $date): array
    {
        return Http::acceptJson()
            ->withHeaders(['User-Agent' => config('services.today.user_agent')])
            ->timeout((int) config('services.today.timeout', 5))
            ->get(rtrim($baseUrl, '/').sprintf('/%02d/%02d', $date->month, $date->day))
            ->throw()
            ->json();
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
}
