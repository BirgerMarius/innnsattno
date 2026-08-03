<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;

class TodayContentService
{
    private const NORWAY = 'Q20';
    private const LIMITS = ['norway_events' => 5, 'world_events' => 3, 'births' => 5, 'deaths' => 5];

    private ResilientDateCache $cache;
    private OnThisDayService $source;

    public function __construct(ResilientDateCache $cache, OnThisDayService $source)
    {
        $this->cache = $cache;
        $this->source = $source;
    }

    public function forDate(CarbonInterface $date): array
    {
        $key = sprintf('curated.v3.%02d-%02d', $date->month, $date->day);

        return $this->cache->remember($key, (int) config('services.today.cache_ttl', 86400), function () use ($date) {
            return $this->curate($this->source->forDate($date), $date);
        }, $this->emptyResult($date));
    }

    public function curate(array $source, CarbonInterface $date): array
    {
        return $this->curateWithDiagnostics($source, $date)['result'];
    }

    public function diagnose(CarbonInterface $date): array
    {
        $source = $this->source->forDate($date);
        return array_merge($this->curateWithDiagnostics($source, $date), [
            'received' => collect($source)->map(fn ($items) => count($items))->all(),
        ]);
    }

    private function curateWithDiagnostics(array $source, CarbonInterface $date): array
    {
        $groups = [
            'events' => $source['events'] ?? [],
            'births' => $source['births'] ?? [],
            'deaths' => $source['deaths'] ?? [],
        ];
        $entities = $this->entities($groups);
        $result = $this->emptyResult($date);
        $diagnostics = ['normalized' => collect($groups)->map(fn ($items) => count($items))->all(), 'sent_to_wikidata' => count($this->candidateIds($groups)), 'enriched' => count($entities), 'classified' => 0, 'accepted' => 0, 'rejected' => [], 'rejected_items' => []];

        foreach ($groups as $type => $items) {
            foreach ($items as $item) {
                [$curated, $reason] = $this->evaluate($item, $type, $entities[$item['wikibase_id'] ?? ''] ?? []);
                if ($curated === null) {
                    $diagnostics['rejected'][$reason] = ($diagnostics['rejected'][$reason] ?? 0) + 1;
                    $diagnostics['rejected_items'][] = ['type' => $type, 'year' => $item['year'] ?? null, 'title' => $item['title'] ?? null, 'reason' => $reason];
                    continue;
                }
                $diagnostics['classified']++;
                $diagnostics['accepted']++;
                $bucket = $type === 'events'
                    ? ($curated['is_norwegian'] ? 'norway_events' : 'world_events')
                    : $type;
                $result[$bucket][] = $curated;
            }
        }

        foreach (self::LIMITS as $bucket => $limit) {
            $result[$bucket] = $this->uniqueRanked($result[$bucket], $limit);
        }

        $diagnostics['selected'] = collect(self::LIMITS)->mapWithKeys(fn ($limit, $bucket) => [$bucket => count($result[$bucket])])->all();
        return ['result' => $result, 'diagnostics' => $diagnostics];
    }

    private function evaluate(array $item, string $type, array $entity): array
    {
        if ($entity === []) {
            return [null, 'mangler_wikidata_berikelse'];
        }
        $fromNorwegianPage = (bool) ($item['norwegian_source'] ?? false);
        $title = $fromNorwegianPage ? trim((string) ($item['title'] ?? '')) : trim((string) data_get($entity, 'labels.nb.value', data_get($entity, 'labels.no.value', '')));
        $description = $fromNorwegianPage ? trim((string) ($item['description'] ?? '')) : trim((string) data_get($entity, 'descriptions.nb.value', data_get($entity, 'descriptions.no.value', '')));
        $text = trim((string) ($item['text'] ?? ''));
        $norwegianPage = data_get($entity, 'sitelinks.nowiki.title');
        $pageLength = (int) ($item['page_length'] ?? 0);
        $citizenships = collect(data_get($entity, 'claims.P27', []))->pluck('mainsnak.datavalue.value.id')->filter()->all();
        $countries = collect(data_get($entity, 'claims.P17', []))->pluck('mainsnak.datavalue.value.id')->filter()->all();
        $isNorwegian = (bool) ($item['norwegian_context'] ?? false)
            || in_array(self::NORWAY, $citizenships, true)
            || in_array(self::NORWAY, $countries, true)
            || $this->mentionsNorway($title.' '.$description.' '.$text);

        if ($title === '' || $description === '') {
            return [null, 'mangler_norsk_etikett_eller_beskrivelse'];
        }
        if (!$this->looksNorwegian($description) || (int) ($item['year'] ?? 0) === 0) {
            return [null, 'norsk_tekst_underkjent'];
        }

        $score = ($isNorwegian ? ($type === 'events' ? 100 : 80) : 0)
            + ($norwegianPage ? 50 : 0)
            + min(40, intdiv($pageLength, 2000));
        if (!$isNorwegian && $pageLength >= 5000) {
            $score += 30;
        }

        $minimum = $isNorwegian ? 80 : 75;
        if ($score < $minimum || (!$isNorwegian && $pageLength < 5000)) {
            return [null, $isNorwegian ? 'under_norsk_relevansterskel' : 'under_internasjonal_betydningsterskel'];
        }

        return [[
            'year' => (int) $item['year'],
            'title' => $title,
            'description' => $description ?: $text,
            'url' => $norwegianPage
                ? 'https://no.wikipedia.org/wiki/'.rawurlencode(str_replace(' ', '_', $norwegianPage))
                : ($item['url'] ?? null),
            'source_name' => $norwegianPage ? 'Wikipedia på norsk' : 'Wikimedia',
            'category' => $type === 'events' ? ($isNorwegian ? 'Norge' : 'Verden') : null,
            'is_norwegian' => $isNorwegian,
            'score' => $score,
        ], null];
    }

    private function entities(array $groups): array
    {
        $ids = $this->candidateIds($groups);
        if ($ids === []) {
            return [];
        }

        $entities = [];
        foreach (array_chunk($ids, 20) as $chunk) {
            try {
                $batch = Http::acceptJson()->withHeaders(['User-Agent' => config('services.today.user_agent')])
                    ->timeout((int) config('services.today.timeout', 5))
                    ->get(config('services.today.wikidata_url'), [
                        'action' => 'wbgetentities', 'ids' => implode('|', $chunk),
                        'props' => 'labels|descriptions|sitelinks|claims', 'languages' => 'nb|no', 'sitefilter' => 'nowiki',
                        'format' => 'json', 'formatversion' => 2,
                    ])->throw()->json('entities', []);
                $entities = array_merge($entities, $batch);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return collect($entities)->filter(fn ($entity) => isset($entity['id']))->keyBy('id')->all();
    }

    private function candidateIds(array $groups): array
    {
        $ids = [];
        foreach ($groups as $items) {
            $count = count($items);
            if ($count === 0) continue;
            $indexes = $count <= 30 ? range(0, $count - 1) : array_map(fn ($i) => (int) round($i * ($count - 1) / 29), range(0, 29));
            foreach ($indexes as $index) {
                if (!empty($items[$index]['wikibase_id'])) $ids[] = $items[$index]['wikibase_id'];
            }
        }
        return array_values(array_unique($ids));
    }

    private function uniqueRanked(array $items, int $limit): array
    {
        usort($items, fn ($a, $b) => ($b['score'] <=> $a['score']) ?: ($a['year'] <=> $b['year']));
        $seen = [];
        $result = [];
        foreach ($items as $item) {
            $key = mb_strtolower($item['year'].'|'.preg_replace('/\W+/u', '', $item['title']));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $item;
            if (count($result) === $limit) {
                break;
            }
        }
        return $result;
    }

    private function mentionsNorway(string $text): bool
    {
        return preg_match('/\b(norge|norsk(?:e|t)?|oslo|bergen|trondheim|samisk(?:e)?|nordmann)\b/ui', $text) === 1;
    }

    private function looksNorwegian(string $text): bool
    {
        if ($text === '') {
            return false;
        }
        $norwegian = preg_match_all('/\b(og|i|på|av|til|som|ble|er|var|norsk|den|det|en|et)\b/ui', $text);
        $english = preg_match_all('/\b(the|and|was|were|of|to|born|died|is)\b/ui', $text);
        $norwegianDescription = preg_match('/\b(norsk|svensk|dansk|finsk|islandsk|tysk|fransk|britisk|amerikansk|russisk|politiker|forfatter|skuespiller|musiker|kunstner|forsker|konge|dronning|krig|slag|ulykke)\b/ui', $text) === 1;
        return ($norwegian >= 1 || $norwegianDescription) && $norwegian >= $english;
    }

    private function emptyResult(CarbonInterface $date): array
    {
        $fixed = config(sprintf('today.fixed.%02d-%02d', $date->month, $date->day), []);
        return array_merge([
            'norway_events' => [], 'world_events' => [], 'births' => [], 'deaths' => [],
            'observance' => null, 'fact' => null,
        ], $fixed);
    }
}
