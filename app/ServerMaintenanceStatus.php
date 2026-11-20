<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServerMaintenanceStatus extends Model
{
    protected $guarded = [];
    protected $casts = ['checked_at' => 'datetime', 'last_success_at' => 'datetime', 'packages' => 'array', 'reboot_required' => 'boolean'];
}
