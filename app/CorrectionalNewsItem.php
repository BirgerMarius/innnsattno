<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CorrectionalNewsItem extends Model
{
    protected $fillable = [
        'correctional_news_cluster_id', 'source_key', 'source_name', 'original_title', 'normalized_title',
        'original_url', 'normalized_url', 'normalized_url_hash', 'published_at', 'fetched_at',
        'relevance_score', 'national_significance_score', 'content_type', 'is_subscription', 'labels',
    ];

    protected $casts = ['published_at' => 'datetime', 'fetched_at' => 'datetime', 'is_subscription' => 'boolean', 'labels' => 'array'];

    public function cluster()
    {
        return $this->belongsTo(CorrectionalNewsCluster::class, 'correctional_news_cluster_id');
    }
}
