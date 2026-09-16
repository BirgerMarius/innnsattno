<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CorrectionalNewsCluster extends Model
{
    protected $fillable = [
        'category', 'display_title', 'primary_source', 'primary_url', 'primary_published_at',
        'primary_is_subscription', 'relevance_score', 'national_significance_score', 'source_count', 'first_seen_at', 'last_seen_at', 'expires_at',
    ];

    protected $casts = [
        'primary_published_at' => 'datetime', 'primary_is_subscription' => 'boolean',
        'first_seen_at' => 'datetime', 'last_seen_at' => 'datetime', 'expires_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(CorrectionalNewsItem::class);
    }
}
