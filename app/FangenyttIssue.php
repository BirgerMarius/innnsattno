<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FangenyttIssue extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'number', 'edition', 'published_at', 'published_locally_at', 'original_url', 'local_file', 'cover_file', 'source', 'status',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'published_locally_at' => 'datetime',
    ];
}
