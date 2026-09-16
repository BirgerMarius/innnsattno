<?php

namespace App\Services;

class CorrectionalNewsRelevanceScorer
{
    private const STRONG_TERMS = [
        'kriminalomsorgen', 'kriminalomsorg', 'kriminalomsorgsdirektoratet', 'kdi', 'straffegjennomføring',
        'friomsorgen', 'fengselsbetjent', 'soningsforhold', 'sikkerhetscelle',
        'fengselskapasitet', 'fengselsplasser', 'ringerike fengsel', 'ila fengsel',
        'kongsvinger fengsel', 'romerike fengsel', 'oslo fengsel', 'ullersmo fengsel',
        'halden fengsel', 'bastøy fengsel', 'skien fengsel', 'bredtveit fengsel',
    ];

    private const POSITIVE_TERMS = [
        'innsatte', 'varetekt', 'isolasjon', 'bemanning', 'sparetiltak', 'spare',
        'budsjettkutt', 'budsjett', 'bevilgning', 'kapasitet', 'tilsyn', 'arbeidsforhold',
        'overtid', 'arbeidstid', 'fengsel', 'fengsler',
    ];

    private const NEGATIVE_TERMS = [
        'dømt', 'dom', 'straffekrav', 'tiltalt', 'rettssak', 'fengselsstraff', 'pågrepet', 'siktet',
    ];

    public function score(array $article, string $sourceKey): int
    {
        $text = $this->normalize(implode(' ', [
            $article['title'] ?? '', $article['description'] ?? '', implode(' ', $article['labels'] ?? []), $article['url'] ?? '',
        ]));
        $strong = $this->matches($text, self::STRONG_TERMS);
        $positive = $this->matches($text, self::POSITIVE_TERMS);
        $negative = $this->matches($text, self::NEGATIVE_TERMS);
        $score = min(80, count($strong) * 40 + count($positive) * 20);

        if (in_array($sourceKey, ['kdi', 'sivilombudet'], true) && ($strong || $positive)) {
            $score += 50;
        }

        if ($negative && ! $strong && count($positive) < 2) {
            $score -= 60;
        }

        return max(0, min(200, $score));
    }

    public function keywords(array $article): array
    {
        $text = $this->normalize(implode(' ', [
            $article['title'] ?? '', implode(' ', $article['labels'] ?? []),
        ]));

        return array_values(array_unique(array_merge(
            $this->matches($text, self::STRONG_TERMS),
            $this->matches($text, self::POSITIVE_TERMS),
            preg_match_all('/\b\d+(?:[.,]\d+)?\b/u', $text, $amounts) ? $amounts[0] : []
        )));
    }

    private function matches(string $text, array $terms): array
    {
        return array_values(array_filter($terms, fn ($term) => str_contains($text, $term)));
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = str_replace(['fengslene', 'fengsler'], 'fengsel', $text);
        return preg_replace('/\s+/u', ' ', preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text) ?? '') ?? '';
    }
}
