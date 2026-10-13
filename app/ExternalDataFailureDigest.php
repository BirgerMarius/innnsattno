<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExternalDataFailureDigest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'period_starts_at' => 'datetime',
        'period_ends_at' => 'datetime',
        'locked_at' => 'datetime',
        'sent_at' => 'datetime',
    ];
}
