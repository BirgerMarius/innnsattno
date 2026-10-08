<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NewsArticleTombstone extends Model
{
    public $timestamps = false;

    protected $fillable = ['news_source_id', 'external_id', 'normalized_url_hash', 'processed_at'];

    protected $casts = ['processed_at' => 'datetime'];

    public function source()
    {
        return $this->belongsTo(NewsSource::class, 'news_source_id');
    }

    public static function hasSeen(int $sourceId, ?string $externalId, string $normalizedUrlHash): bool
    {
        return static::where('news_source_id', $sourceId)
            ->where(function ($query) use ($externalId, $normalizedUrlHash) {
                $query->where('normalized_url_hash', $normalizedUrlHash);
                if ($externalId) {
                    $query->orWhere('external_id', $externalId);
                }
            })
            ->exists();
    }
}
