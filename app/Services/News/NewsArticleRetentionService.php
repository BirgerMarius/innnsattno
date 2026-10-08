<?php

namespace App\Services\News;

use App\NewsArticle;
use App\NewsArticleTombstone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NewsArticleRetentionService
{
    public function cutoff(): Carbon
    {
        return now('Europe/Oslo')->subDays(10);
    }

    public function isExpired(?Carbon $publishedAt, Carbon $fallbackDate): bool
    {
        return ($publishedAt ?: $fallbackDate)->copy()->setTimezone('Europe/Oslo')->lt($this->cutoff());
    }

    public function hasBeenProcessed(int $sourceId, ?string $externalId, string $normalizedUrlHash): bool
    {
        return NewsArticleTombstone::hasSeen($sourceId, $externalId, $normalizedUrlHash);
    }

    public function remember(int $sourceId, ?string $externalId, string $normalizedUrlHash): void
    {
        $existing = NewsArticleTombstone::where('news_source_id', $sourceId)
            ->where(function ($query) use ($externalId, $normalizedUrlHash) {
                $query->where('normalized_url_hash', $normalizedUrlHash);
                if ($externalId) {
                    $query->orWhere('external_id', $externalId);
                }
            })
            ->first();

        if (! $existing) {
            NewsArticleTombstone::create([
                'news_source_id' => $sourceId,
                'external_id' => $externalId,
                'normalized_url_hash' => $normalizedUrlHash,
                'processed_at' => now('Europe/Oslo'),
            ]);
        }
    }

    public function discard(NewsArticle $article): void
    {
        DB::transaction(function () use ($article) {
            $this->remember($article->news_source_id, $article->external_id, $article->normalized_url_hash);
            $article->delete();
        });
    }

    public function prune(): int
    {
        $publicDate = 'COALESCE(published_at, fetched_at, created_at)';
        $deleted = 0;

        NewsArticle::whereRaw("{$publicDate} < ?", [$this->cutoff()])
            ->orderBy('id')
            ->chunkById(100, function ($articles) use (&$deleted) {
                foreach ($articles as $article) {
                    $this->discard($article);
                    $deleted++;
                }
            });

        return $deleted;
    }
}
