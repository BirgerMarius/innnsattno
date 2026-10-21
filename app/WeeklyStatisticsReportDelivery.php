<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WeeklyStatisticsReportDelivery extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
        'last_attempt_at' => 'datetime',
    ];
}
