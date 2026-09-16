<?php

namespace App\Services;

class CorrectionalNewsNationalSignificanceScorer
{
    private const OPINION_URL_SEGMENTS = ['/debatt/', '/meninger/', '/kommentar/', '/kronikk/'];
    private const OPINION_LABELS = ['debatt', 'meninger', 'kommentar', 'kronikk'];
    private const NATIONAL_SIGNALS = [
        'sparetiltak', 'spare', 'budsjett', 'bevilgning', 'fengselskapasitet', 'fengselsplasser',
        'soningskø', 'nasjonal politikk', 'forskrift', 'lovendring', 'landsdekkende bemanning',
        'nedleggelse', 'åpning av fengsel', 'systemisk', 'ulovlig isolasjon', 'sikkerhetscelle',
        'torturforebygging', 'nasjonal rapport', 'landsdekkende',
    ];

    public function assess(array $article): array
    {
        $url = mb_strtolower((string) ($article['url'] ?? ''));
        $feedLabels = array_map(fn ($label) => mb_strtolower(trim((string) $label)), $article['labels'] ?? []);
        $opinion = collect(self::OPINION_URL_SEGMENTS)->contains(fn ($segment) => str_contains($url, $segment))
            || collect($feedLabels)->contains(fn ($label) => in_array($label, self::OPINION_LABELS, true));
        // National importance must be explicit in the headline or feed metadata;
        // a passing mention in a short description is too prone to false positives.
        $text = $this->normalize(implode(' ', [$article['title'] ?? '', implode(' ', $feedLabels)]));
        $signals = array_values(array_filter(self::NATIONAL_SIGNALS, fn ($signal) => str_contains($text, $signal)));

        if ($opinion) {
            return ['score' => 0, 'content_type' => 'opinion', 'signals' => $signals];
        }

        $score = 0;
        foreach ($signals as $signal) {
            $score = max($score, match ($signal) {
                'sparetiltak', 'spare', 'budsjett', 'bevilgning', 'fengselskapasitet', 'fengselsplasser', 'soningskø',
                'forskrift', 'lovendring', 'landsdekkende bemanning', 'nedleggelse', 'åpning av fengsel', 'systemisk', 'ulovlig isolasjon',
                'sikkerhetscelle', 'torturforebygging', 'nasjonal rapport', 'landsdekkende' => 80,
                default => 60,
            });
        }

        return ['score' => $score, 'content_type' => $this->contentType($url), 'signals' => $signals];
    }

    private function contentType(string $url): string
    {
        if (str_contains($url, '/pressemelding/')) return 'press_release';
        if (str_contains($url, '/uttalelser/') || str_contains($url, '/tortur-forebygging/')) return 'oversight';
        return 'news';
    }

    private function normalize(string $text): string
    {
        return preg_replace('/\s+/u', ' ', preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($text)) ?? '') ?? '';
    }
}
