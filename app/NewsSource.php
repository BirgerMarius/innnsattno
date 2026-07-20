<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NewsSource extends Model
{
    protected $fillable = ['name', 'slug', 'country', 'website_url', 'feed_url', 'source_type', 'is_active', 'last_fetched_at', 'last_success_at', 'last_error'];

    protected $casts = ['is_active' => 'boolean', 'last_fetched_at' => 'datetime', 'last_success_at' => 'datetime'];

    public function articles()
    {
        return $this->hasMany(NewsArticle::class);
    }
}
