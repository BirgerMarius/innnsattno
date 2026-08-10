<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class WikidataQuizSourceService
{
    public function facts(string $category, string $language): array
    {
        $cacheKey = "quiz.wikidata.{$category}.{$language}.v1";
        $lastGoodKey = $cacheKey.'.last_good';

        if (Cache::has($cacheKey)) {
            return (array) Cache::get($cacheKey);
        }

        try {
            $facts = $this->fetch($category, $language);

            if (!$facts) {
                throw new RuntimeException('Wikidata returnerte ingen brukbare quizfakta.');
            }

            Cache::put($cacheKey, $facts, now()->addSeconds((int) config('services.wikidata.cache_ttl', 86400)));
            Cache::put($lastGoodKey, $facts, now()->addDays(7));

            return $facts;
        } catch (Throwable $exception) {
            $stale = Cache::get($lastGoodKey);

            if (is_array($stale) && $stale) {
                return $stale;
            }

            throw new RuntimeException('Wikidata kunne ikke levere quizdata.', 0, $exception);
        }
    }

    private function fetch(string $category, string $language): array
    {
        $bindings = [];
        $patterns = $this->patterns()[$category] ?? [];
        if (!$patterns) {
            throw new RuntimeException('Kategorien har ingen Wikidata-spørring.');
        }

        foreach ([$this->query($patterns, $language, $category)] as $query) {
            $response = Http::acceptJson()
                ->withHeaders(['User-Agent' => config('services.wikidata.user_agent')])
                ->timeout((int) config('services.wikidata.timeout', 20))
                ->retry(2, 500)
                ->get((string) config('services.wikidata.sparql_url'), [
                    'query' => $query,
                    'format' => 'json',
                ])
                ->throw();

            $result = $response->json('results.bindings');
            if (!is_array($result)) {
                throw new RuntimeException('Wikidata returnerte et ugyldig resultat.');
            }

            $bindings = array_merge($bindings, $result);
        }

        $facts = [];

        foreach ($bindings as $binding) {
            $subject = trim((string) ($binding['subjectLabel']['value'] ?? ''));
            $answer = trim((string) ($binding['literalAnswer']['value'] ?? $binding['answerLabel']['value'] ?? ''));
            $factType = (string) ($binding['factType']['value'] ?? '');
            $sourceUrl = (string) ($binding['subject']['value'] ?? '');

            if ($subject === '' || $answer === '' || $factType === ''
                || preg_match('/^Q\d+$/', $subject) || preg_match('/^Q\d+$/', $answer)
                || mb_strtolower($subject) === mb_strtolower($answer)) {
                continue;
            }

            $key = mb_strtolower($factType.'|'.$subject.'|'.$answer);
            $facts[$key] = [
                'fact_type' => $factType,
                'subject' => $subject,
                'subject_id' => $this->entityId($sourceUrl),
                'answer' => $answer,
                'popularity' => (int) ($binding['popularity']['value'] ?? 0),
                'category' => $category,
                'source' => 'Wikidata',
                'source_url' => $sourceUrl,
            ];
        }

        return array_values($facts);
    }

    private function entityId(string $url): string
    {
        preg_match('/(Q\d+)$/', $url, $matches);

        return $matches[1] ?? '';
    }

    private function query(array $patterns, string $language, string $category): string
    {
        $languageCode = $language === 'en' ? 'en' : 'nb,no,en';
        $limit = count($patterns) === 1 ? 140 : 240;
        $popularityFilter = $category === 'literature_language' ? '  FILTER(?popularity >= 20)' : '';
        $pattern = count($patterns) === 1
            ? $patterns[0]."\n  ?subject wikibase:sitelinks ?popularity.\n{$popularityFilter}"
            : implode("\nUNION\n", array_map(function (string $item): string {
                return "{\n  SELECT ?subject ?answer ?literalAnswer ?factType ?popularity WHERE {\n    {$item}\n    ?subject wikibase:sitelinks ?popularity.\n  } LIMIT 60\n}";
            }, $patterns));

        return <<<SPARQL
SELECT DISTINCT ?subject ?subjectLabel ?answer ?answerLabel ?literalAnswer ?factType ?popularity WHERE {
{$pattern}
  SERVICE wikibase:label { bd:serviceParam wikibase:language "{$languageCode}". }
}
LIMIT {$limit}
SPARQL;
    }

    private function patterns(): array
    {
        return [
            'norway' => [
                '?subject wdt:P31 wd:Q5; wdt:P27 wd:Q20; wdt:P19 ?birthplace; wdt:P569 ?date. ?birthplace wdt:P17 wd:Q20. '
                    .'BIND(STR(YEAR(?date)) AS ?literalAnswer) BIND("birth_year" AS ?factType)',
                '?subject wdt:P31 wd:Q5; wdt:P27 wd:Q20; wdt:P19 ?birthplace; wdt:P106 ?answer. ?birthplace wdt:P17 wd:Q20. '
                    .'FILTER NOT EXISTS { ?subject wdt:P106 ?other. FILTER(?other != ?answer) } BIND("occupation" AS ?factType)',
                '?subject wdt:P31 wd:Q5; wdt:P27 wd:Q20; wdt:P19 ?birthplace; wdt:P641 ?answer. ?birthplace wdt:P17 wd:Q20. '
                    .'FILTER NOT EXISTS { ?subject wdt:P641 ?other. FILTER(?other != ?answer) } BIND("athlete_sport" AS ?factType)',
            ],
            'geography' => [
                '?subject wdt:P31 wd:Q6256; wdt:P36 ?answer. '
                    .'FILTER NOT EXISTS { ?subject wdt:P36 ?other. FILTER(?other != ?answer) } BIND("capital" AS ?factType)',
            ],
            'science' => [
                '?subject wdt:P31 wd:Q11344; wdt:P246 ?literalAnswer. BIND("element_symbol" AS ?factType)',
            ],
            'literature_language' => [
                'VALUES ?answer { wd:Q692 wd:Q34660 wd:Q892 wd:Q3335 wd:Q35040 wd:Q5686 wd:Q3398 } '
                    .'?subject wdt:P31 wd:Q571; wdt:P50 ?answer. '
                    .'FILTER NOT EXISTS { ?subject wdt:P50 ?other. FILTER(?other != ?answer) } BIND("author" AS ?factType)',
            ],
        ];
    }
}
