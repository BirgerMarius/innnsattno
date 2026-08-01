<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OnThisDayService
{
    private ResilientDateCache $cache;

    public function __construct(ResilientDateCache $cache)
    {
        $this->cache = $cache;
    }

    public function forDate(CarbonInterface $date): array
    {
        $key = sprintf('wikimedia.%02d-%02d', $date->month, $date->day);

        return $this->cache->remember(
            $key,
            (int) config('services.today.cache_ttl', 86400),
            fn () => $this->fetch($date),
            ['events' => [], 'births' => [], 'deaths' => []]
        );
    }

    private function fetch(CarbonInterface $date): array
    {
        $url = rtrim(config('services.today.wikimedia_url'), '/')
            .sprintf('/%02d/%02d', $date->month, $date->day);
        $payload = Http::acceptJson()
            ->withHeaders(['User-Agent' => config('services.today.user_agent')])
            ->timeout((int) config('services.today.timeout', 5))
            ->get($url)
            ->throw()
            ->json();

        $normalized = [
            'events' => $this->normalize($payload['events'] ?? [], 'event'),
            'births' => $this->normalize($payload['births'] ?? [], 'person'),
            'deaths' => $this->normalize($payload['deaths'] ?? [], 'person'),
        ];

        if ($normalized['events'] === [] && $normalized['births'] === [] && $normalized['deaths'] === []) {
            throw new RuntimeException('Wikimedia returnerte ingen brukbare oppføringer.');
        }

        $norwegianPages = $this->norwegianPages($normalized);
        foreach ($normalized as &$items) {
            foreach ($items as &$item) {
                if ($item['wikibase_id'] && isset($norwegianPages[$item['wikibase_id']])) {
                    $item['url'] = 'https://no.wikipedia.org/wiki/'.rawurlencode(str_replace(' ', '_', $norwegianPages[$item['wikibase_id']]));
                    $item['has_norwegian_page'] = true;
                }
                unset($item['wikibase_id']);
            }
            unset($item);
        }
        unset($items);

        return [
            'events' => $this->balancedEvents($normalized['events'], 7),
            'births' => $this->rankPeople($normalized['births'], 5),
            'deaths' => $this->rankPeople($normalized['deaths'], 5),
        ];
    }

    private function normalize($entries, string $kind): array
    {
        if (!is_array($entries)) {
            return [];
        }

        $seen = [];
        $items = [];
        foreach ($entries as $entry) {
            $year = filter_var($entry['year'] ?? null, FILTER_VALIDATE_INT);
            $text = trim(strip_tags((string) ($entry['text'] ?? '')));
            $page = is_array($entry['pages'][0] ?? null) ? $entry['pages'][0] : [];
            $name = trim(strip_tags((string) data_get($page, 'titles.normalized', '')));
            $description = trim(strip_tags((string) ($page['description'] ?? '')));

            if ($year === false || $text === '' || ($kind === 'person' && $name === '')) {
                continue;
            }

            $identity = mb_strtolower($year.'|'.($kind === 'person' ? $name : $text));
            if (isset($seen[$identity])) {
                continue;
            }
            $seen[$identity] = true;

            $items[] = [
                'year' => $year,
                'title' => $kind === 'person' ? $name : ($name ?: $text),
                'text' => $text,
                'description' => $description,
                'url' => data_get($page, 'content_urls.desktop.page'),
                'wikibase_id' => $page['wikibase_item'] ?? null,
                'has_norwegian_page' => false,
            ];
        }

        return $items;
    }

    private function norwegianPages(array $groups): array
    {
        $ids = [];
        foreach ($groups as $items) {
            foreach (array_slice($items, 0, 16) as $item) {
                if ($item['wikibase_id']) {
                    $ids[] = $item['wikibase_id'];
                }
            }
        }
        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            return [];
        }

        try {
            $entities = Http::acceptJson()
                ->withHeaders(['User-Agent' => config('services.today.user_agent')])
                ->timeout((int) config('services.today.timeout', 5))
                ->get(config('services.today.wikidata_url'), [
                    'action' => 'wbgetentities',
                    'ids' => implode('|', $ids),
                    'props' => 'sitelinks',
                    'sitefilter' => 'nowiki',
                    'format' => 'json',
                    'formatversion' => 2,
                ])->throw()->json('entities', []);
        } catch (\Throwable $exception) {
            report($exception);
            return [];
        }

        $pages = [];
        foreach ($entities as $entity) {
            $id = $entity['id'] ?? null;
            $title = data_get($entity, 'sitelinks.nowiki.title');
            if ($id && $title) {
                $pages[$id] = $title;
            }
        }

        return $pages;
    }

    private function balancedEvents(array $events, int $limit): array
    {
        usort($events, fn ($a, $b) => $a['year'] <=> $b['year']);
        if (count($events) <= $limit) {
            return $events;
        }

        $selected = [];
        $lastIndex = count($events) - 1;
        for ($i = 0; $i < $limit; $i++) {
            $selected[] = $events[(int) round($i * $lastIndex / ($limit - 1))];
        }

        return $selected;
    }

    private function rankPeople(array $people, int $limit): array
    {
        usort($people, fn ($a, $b) => ($b['has_norwegian_page'] <=> $a['has_norwegian_page']) ?: ($b['year'] <=> $a['year']));

        return array_slice($people, 0, $limit);
    }
}
