<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExternalDataSourceEvent extends Model
{
    public const OUTCOME_FAILURE = 'failure';
    public const OUTCOME_SUCCESS = 'success';

    protected $guarded = [];

    protected $casts = [
        'occurred_at' => 'datetime',
        'diagnostics' => 'array',
    ];
}
