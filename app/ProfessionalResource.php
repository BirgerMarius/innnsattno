<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProfessionalResource extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Kladd',
        self::STATUS_PUBLISHED => 'Publisert',
    ];

    public const MEDIA_TYPES = [
        'article' => ['label' => 'Artikkel', 'icon' => '📄'],
        'report' => ['label' => 'Rapport', 'icon' => '📘'],
        'guide' => ['label' => 'Veileder', 'icon' => '📚'],
        'podcast' => ['label' => 'Podkast', 'icon' => '🎧'],
        'video' => ['label' => 'Video', 'icon' => '🎥'],
        'documentary' => ['label' => 'Dokumentar', 'icon' => '📺'],
        'website' => ['label' => 'Nettside', 'icon' => '🌐'],
        'legislation' => ['label' => 'Lovverk', 'icon' => '⚖️'],
        'statistics' => ['label' => 'Statistikk', 'icon' => '📊'],
    ];

    protected $fillable = [
        'category_id',
        'title',
        'url',
        'comment',
        'publisher',
        'content_type',
        'media_type',
        'publication_year',
        'is_featured',
        'status',
        'sort_order',
        'last_checked_at',
        'published_at',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'publication_year' => 'integer',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'last_checked_at' => 'date',
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(ResourceCategory::class, 'category_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'professional_resource_tag')
            ->orderBy('name');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function mediaTypeLabel(): ?string
    {
        return self::MEDIA_TYPES[$this->media_type]['label'] ?? null;
    }

    public function mediaTypeIcon(): ?string
    {
        return self::MEDIA_TYPES[$this->media_type]['icon'] ?? null;
    }

    public function mediaTypeDisplay(): ?string
    {
        if (! $this->media_type || ! isset(self::MEDIA_TYPES[$this->media_type])) {
            return null;
        }

        return self::MEDIA_TYPES[$this->media_type]['icon'] . ' ' . self::MEDIA_TYPES[$this->media_type]['label'];
    }
}
