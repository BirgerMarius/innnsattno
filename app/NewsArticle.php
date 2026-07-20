<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NewsArticle extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_HIDDEN = 'hidden';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_PENDING => 'Nye', self::STATUS_PUBLISHED => 'Publiserte',
        self::STATUS_HIDDEN => 'Skjulte', self::STATUS_ARCHIVED => 'Arkiverte',
    ];

    protected $fillable = ['news_source_id', 'external_id', 'original_url', 'normalized_url', 'original_title', 'original_excerpt', 'original_author', 'image_url', 'published_at', 'fetched_at', 'status', 'edited_title', 'edited_excerpt', 'approved_at', 'approved_by'];

    protected $casts = ['published_at' => 'datetime', 'fetched_at' => 'datetime', 'approved_at' => 'datetime'];

    public function setNormalizedUrlAttribute(string $url): void
    {
        $this->attributes['normalized_url'] = $url;
        $this->attributes['normalized_url_hash'] = self::normalizedUrlHash($url);
    }

    public static function normalizedUrlHash(string $url): string
    {
        return hash('sha256', $url);
    }

    public function source() { return $this->belongsTo(NewsSource::class, 'news_source_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function displayTitle(): string { return $this->edited_title ?: $this->original_title; }
    public function displayExcerpt(): ?string { return $this->edited_excerpt ?: $this->original_excerpt; }
}
