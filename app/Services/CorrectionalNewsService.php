<?php

namespace App\Services;

use App\CorrectionalNewsCluster;
use App\CorrectionalNewsItem;
use App\Services\CorrectionalNews\CorrectionalNewsSource;
use App\Services\CorrectionalNews\KdiRssSource;
use App\Services\CorrectionalNews\KyNewsSource;
use App\Services\CorrectionalNews\NffNewsSource;
use App\Services\CorrectionalNews\SivilombudetRssSource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CorrectionalNewsService
{
    public function __construct(
        private CorrectionalNewsRelevanceScorer $scorer,
        private CorrectionalNewsNationalSignificanceScorer $nationalSignificanceScorer,
        private CorrectionalNewsClusterer $clusterer,
        private ExternalDataFailureNotifier $notifier,
        private NffNewsSource $nff,
        private KyNewsSource $ky,
        private KdiRssSource $kdi,
        private SivilombudetRssSource $sivilombudet,
    ) {
    }

    public function refresh(?string $sourceKey = null): array
    {
        $reports = [];
        foreach ($this->sources() as $source) {
            if ($sourceKey && $source->key() !== $sourceKey) {
                continue;
            }
            $reports[] = $this->refreshSource($source);
        }
        Cache::forget('correctional-news.front-page');
        return $reports;
    }

    public function frontPage(): array
    {
        // A safe empty result also makes a deployment before migrations harmless.
        if (! Schema::hasTable('correctional_news_clusters') || ! Schema::hasTable('correctional_news_items')) {
            return ['national' => [], 'union' => []];
        }

        return Cache::remember('correctional-news.front-page', config('correctional_news.front_page_cache_seconds', 300), function () {
            $now = now();
            // Union articles remain union candidates for 30 days. The strongest ones may
            // also be selected as national front-page stories, but are suppressed from
            // the union list only when they were actually selected there.
            $nationalClusters = $this->clusters('national', $now)
                ->merge($this->clusters('union', $now))
                ->filter(fn ($cluster) => $cluster->national_significance_score >= config('correctional_news.national_significance_threshold', 60))
                ->sort(function ($left, $right) {
                    foreach ([
                        $right->national_significance_score <=> $left->national_significance_score,
                        $right->relevance_score <=> $left->relevance_score,
                        ($right->primary_published_at?->getTimestamp() ?? 0) <=> ($left->primary_published_at?->getTimestamp() ?? 0),
                    ] as $comparison) if ($comparison !== 0) return $comparison;
                    return 0;
                })->take(3)->values();
            $national = $nationalClusters->map(fn ($cluster) => $this->display($cluster))->all();
            $nationalIds = $nationalClusters->pluck('id')->all();
            $union = $this->unionSelection($now, $nationalIds);

            return compact('national', 'union');
        });
    }

    private function refreshSource(CorrectionalNewsSource $source): array
    {
        $report = [
            'source' => $source->key(), 'found' => 0, 'stored' => 0, 'updated' => 0, 'duplicates' => 0,
            'rejected' => 0, 'clusters_created' => 0, 'clusters_updated' => 0, 'error' => null,
        ];
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'innsatt.no correctional-news/1.0 (+https://innsatt.no)',
                'Accept' => 'application/rss+xml, application/xml, text/xml',
            ])->connectTimeout(5)->timeout(15)->get($source->url());
            $response->throw();
            $articles = $source->parse($response->body());
            $report['found'] = count($articles);
            foreach ($articles as $article) {
                $outcome = $this->store($article, $source);
                if ($outcome === 'stored') {
                    $report['stored']++;
                    $report['clusters_updated']++;
                } elseif ($outcome === 'cluster_created') {
                    $report['stored']++;
                    $report['clusters_created']++;
                } elseif ($outcome === 'duplicate') {
                    $report['duplicates']++;
                } elseif ($outcome === 'updated') {
                    $report['updated']++;
                } else {
                    $report['rejected']++;
                }
            }
            Cache::put('correctional-news.source-success:'.$source->key(), now()->toIso8601String(), now()->addDay());
        } catch (Throwable $exception) {
            $report['error'] = mb_substr($exception->getMessage(), 0, 500);
            $context = ['operation' => $source->key(), 'failure_kind' => $this->failureKind($exception)];
            if ($exception instanceof RequestException && $exception->response !== null) {
                $context['status'] = $exception->response->status();
            }
            $this->notifier->report('correctional-news', 'Forsidens nyhetskilde kunne ikke hentes eller leses.', $context);
        }
        return $report;
    }

    private function store(array $article, CorrectionalNewsSource $source): string
    {
        $url = $this->normalizeUrl($article['url'] ?? '');
        $title = trim(strip_tags((string) ($article['title'] ?? '')));
        if (! $url || $title === '') {
            return 'rejected';
        }
        $published = $this->date($article['published_at'] ?? null);
        $article['published_at'] = $published;
        $score = $this->scorer->score($article, $source->key());
        $nationalAssessment = $this->nationalSignificanceScorer->assess($article);
        $requestedCategory = $source->kind() === 'union' ? 'union' : 'national';
        if ($source->kind() === 'national' && $score < config('correctional_news.national_threshold', 80)) {
            return 'rejected';
        }

        $hash = hash('sha256', $url);
        $existing = CorrectionalNewsItem::where('normalized_url_hash', $hash)->first();
        if ($existing) {
            $existing->update([
                'fetched_at' => now(), 'relevance_score' => $score,
                'national_significance_score' => $nationalAssessment['score'], 'content_type' => $nationalAssessment['content_type'],
                'labels' => ['feed' => $article['labels'] ?? [], 'keywords' => $this->scorer->keywords($article), 'national_signals' => $nationalAssessment['signals']],
            ]);
            if ($existing->cluster) $this->selectPrimary($existing->cluster->fresh('items'));
            return 'updated';
        }

        $keywords = $this->scorer->keywords($article);
        $cluster = $this->clusterer->findMatchingCluster($article, $requestedCategory, $keywords, $source->key());
        $category = $cluster ? $cluster->category : $requestedCategory;
        $expires = now()->addDays($category === 'union' ? config('correctional_news.union_max_age_days', 30) : config('correctional_news.national_max_age_days', 10));
        $createdCluster = ! $cluster;
        if ($createdCluster) {
            $cluster = CorrectionalNewsCluster::create([
                'category' => $category, 'display_title' => $title, 'primary_source' => $source->name(), 'primary_url' => $url,
                'primary_published_at' => $published, 'primary_is_subscription' => (bool) ($article['is_subscription'] ?? false),
                'relevance_score' => $score, 'national_significance_score' => $nationalAssessment['score'], 'source_count' => 0, 'first_seen_at' => now(), 'last_seen_at' => now(), 'expires_at' => $expires,
            ]);
        }
        $item = CorrectionalNewsItem::create([
            'correctional_news_cluster_id' => $cluster->id, 'source_key' => $source->key(), 'source_name' => $source->name(),
            'original_title' => $title, 'normalized_title' => $this->clusterer->normalizedTitle($title),
            'original_url' => $url, 'normalized_url' => $url, 'normalized_url_hash' => $hash,
            'published_at' => $published, 'fetched_at' => now(), 'relevance_score' => $score,
            'national_significance_score' => $nationalAssessment['score'], 'content_type' => $nationalAssessment['content_type'],
            'is_subscription' => (bool) ($article['is_subscription'] ?? false),
            'labels' => ['feed' => $article['labels'] ?? [], 'keywords' => $keywords, 'national_signals' => $nationalAssessment['signals']],
        ]);
        $cluster->update(['last_seen_at' => now(), 'expires_at' => $expires, 'source_count' => $cluster->items()->count()]);
        $this->selectPrimary($cluster->fresh('items'));
        return $createdCluster ? 'cluster_created' : 'stored';
    }

    private function selectPrimary(CorrectionalNewsCluster $cluster): void
    {
        $items = $cluster->items->all();
        usort($items, function (CorrectionalNewsItem $left, CorrectionalNewsItem $right): int {
            $primaryRank = fn (CorrectionalNewsItem $item) => match ($item->source_key) {
                'kdi', 'sivilombudet' => 3,
                'nff', 'ky' => 2,
                default => 1,
            };
            foreach ([
                $primaryRank($right) <=> $primaryRank($left),
                ((int) ! $right->is_subscription) <=> ((int) ! $left->is_subscription),
                $right->relevance_score <=> $left->relevance_score,
                str_word_count($right->original_title) <=> str_word_count($left->original_title),
            ] as $comparison) {
                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return ($left->published_at?->getTimestamp() ?? PHP_INT_MAX) <=> ($right->published_at?->getTimestamp() ?? PHP_INT_MAX);
        });
        $item = $items[0] ?? null;
        if (! $item) {
            return;
        }
        $unionOnly = $cluster->items->every(fn ($candidate) => in_array($candidate->source_key, ['nff', 'ky'], true));
        $category = $unionOnly ? 'union' : 'national';
        $cluster->update([
            'category' => $category,
            'display_title' => $item->original_title, 'primary_source' => $item->source_name, 'primary_url' => $item->original_url,
            'primary_published_at' => $item->published_at, 'primary_is_subscription' => $item->is_subscription,
            'relevance_score' => $cluster->items->max('relevance_score'),
            'national_significance_score' => $cluster->items->max('national_significance_score'), 'source_count' => $cluster->items->count(),
            'expires_at' => now()->addDays($category === 'union' ? config('correctional_news.union_max_age_days', 30) : config('correctional_news.national_max_age_days', 10)),
        ]);
    }

    private function clusters(string $category, Carbon $now)
    {
        $maxAgeDays = $category === 'union'
            ? config('correctional_news.union_max_age_days', 30)
            : config('correctional_news.national_max_age_days', 10);
        return CorrectionalNewsCluster::where('category', $category)->where('expires_at', '>', $now)
            ->where('primary_published_at', '>', $now->copy()->subDays($maxAgeDays))
            ->orderByDesc('primary_published_at')->get();
    }

    private function unionSelection(Carbon $now, array $nationalIds): array
    {
        $clusters = $this->clusters('union', $now)->whereNotIn('id', $nationalIds);
        $bySource = [];
        foreach ($clusters as $cluster) {
            $sources = $cluster->items()->pluck('source_key')->all();
            foreach (array_unique($sources) as $source) {
                $bySource[$source] ??= $cluster;
            }
        }
        $selected = collect($bySource)->filter()->unique('id')->values();
        $remaining = $clusters->reject(fn ($cluster) => $selected->contains('id', $cluster->id))
            ->sortByDesc('primary_published_at')->take(max(0, 5 - $selected->count()));
        return $selected->merge($remaining)->unique('id')->sortByDesc('primary_published_at')
            ->map(fn ($cluster) => $this->display($cluster, true))->values()->all();
    }

    private function display(CorrectionalNewsCluster $cluster, bool $union = false): array
    {
        $sourceKeys = $cluster->items()->pluck('source_key')->unique()->all();
        $label = $union && in_array('nff', $sourceKeys, true) && in_array('ky', $sourceKeys, true) ? 'NFF/KY' : ($union ? strtoupper($sourceKeys[0] ?? '') : $cluster->primary_source);
        return [
            'title' => $cluster->display_title, 'url' => $cluster->primary_url, 'source' => $cluster->primary_source,
            'label' => $label, 'published_at' => $cluster->primary_published_at?->locale('nb')->translatedFormat('j. M Y'),
            'is_subscription' => $cluster->primary_is_subscription, 'source_count' => $cluster->source_count,
        ];
    }

    private function sources(): array { return [$this->nff, $this->ky, $this->kdi, $this->sivilombudet]; }
    private function date(?string $value): ?Carbon { try { return $value ? Carbon::parse($value) : null; } catch (Throwable) { return null; } }
    private function normalizeUrl(string $url): ?string
    {
        $parts = parse_url(trim($url));
        if (! is_array($parts) || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])) return null;
        parse_str($parts['query'] ?? '', $query);
        foreach (array_keys($query) as $key) if (str_starts_with(strtolower($key), 'utm_') || in_array(strtolower($key), ['fbclid', 'gclid'], true)) unset($query[$key]);
        $normalized = strtolower($parts['scheme']).'://'.strtolower($parts['host']).($parts['path'] ?? '/');
        return $query ? $normalized.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986) : $normalized;
    }
    private function failureKind(Throwable $exception): string { return $exception instanceof ConnectionException ? 'connection_failure' : ($exception instanceof RequestException ? 'http_status' : 'invalid_payload'); }
}
