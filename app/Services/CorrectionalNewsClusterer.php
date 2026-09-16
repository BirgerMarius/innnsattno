<?php

namespace App\Services;

use App\CorrectionalNewsCluster;
use App\CorrectionalNewsItem;
use Illuminate\Support\Carbon;

class CorrectionalNewsClusterer
{
    private const STOP_WORDS = [
        'og', 'i', 'på', 'av', 'for', 'til', 'med', 'om', 'at', 'en', 'et', 'den', 'det', 'de', 'skal', 'må',
        'norske', 'norsk', 'kroner', 'krone', 'millioner', 'million', 'sier', 'ny', 'nye', 'fra', 'er', 'blir',
    ];

    private const GENERIC_TERMS = ['kriminalomsorgen', 'kriminalomsorg', 'fengsel', 'fengsler', 'innsatte'];

    public function normalizedTitle(string $title): string
    {
        $tokens = $this->tokens($title);
        return implode(' ', $tokens);
    }

    public function findMatchingCluster(array $article, string $category, array $keywords, string $sourceKey): ?CorrectionalNewsCluster
    {
        $published = $article['published_at'] instanceof Carbon ? $article['published_at'] : now();
        $from = $published->copy()->subHours((int) config('correctional_news.cluster_window_hours', 72));
        $to = $published->copy()->addHours((int) config('correctional_news.cluster_window_hours', 72));

        return CorrectionalNewsCluster::with('items')
            ->where('category', $category)
            ->where('expires_at', '>', now())
            ->whereBetween('primary_published_at', [$from, $to])
            ->get()
            ->first(fn ($cluster) => $this->matches($article['title'], $keywords, $cluster, $sourceKey));
    }

    private function matches(string $title, array $keywords, CorrectionalNewsCluster $cluster, string $sourceKey): bool
    {
        $left = $this->tokens($title);
        $right = $this->tokens($cluster->display_title);
        $union = array_unique(array_merge($left, $right));
        $intersection = array_intersect($left, $right);
        $similarity = count($union) ? count($intersection) / count($union) : 0;
        $clusterSources = $cluster->items->pluck('source_key')->all();
        $bothUnionSources = in_array($sourceKey, ['nff', 'ky'], true)
            && count(array_intersect($clusterSources, ['nff', 'ky'])) > 0;
        $threshold = $bothUnionSources || $cluster->category === 'union' ? 0.65 : 0.60;
        if ($similarity < $threshold) {
            return false;
        }

        $clusterKeywords = $cluster->items->flatMap(function ($item) {
            $labels = $item->labels ?? [];
            return $labels['keywords'] ?? $labels;
        })->map(fn ($word) => mb_strtolower($word))->all();
        $shared = array_values(array_intersect($keywords, $clusterKeywords));
        $distinctive = array_diff($shared, self::GENERIC_TERMS);

        return count($distinctive) >= 1 || count($shared) >= 2;
    }

    private function tokens(string $title): array
    {
        $text = mb_strtolower($title);
        $text = str_replace(['fengslene', 'fengsler'], 'fengsel', $text);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text) ?? '';
        $tokens = preg_split('/\s+/u', trim($text)) ?: [];

        return array_values(array_unique(array_filter($tokens, fn ($token) => mb_strlen($token) > 1 && ! in_array($token, self::STOP_WORDS, true))));
    }
}
