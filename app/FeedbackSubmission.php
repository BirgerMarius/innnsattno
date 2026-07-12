<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FeedbackSubmission extends Model
{
    protected $fillable = [
        'type',
        'title',
        'message',
        'is_anonymous',
        'name',
        'email',
        'status',
        'admin_note',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];
}
