<?php

return [
    // Written by the root-owned collector; Laravel never invokes APT itself.
    'status_path' => env('SERVER_MAINTENANCE_STATUS_PATH', storage_path('app/server-maintenance/status.json')),
    'error_path' => env('SERVER_MAINTENANCE_ERROR_PATH', storage_path('app/server-maintenance/status.error.json')),
    'enabled' => env('SERVER_MAINTENANCE_EMAIL_ENABLED', false),
    'recipient' => env('SERVER_MAINTENANCE_EMAIL_RECIPIENT', env('FEEDBACK_NOTIFICATION_EMAIL')),
    'stale_after_hours' => (int) env('SERVER_MAINTENANCE_STALE_AFTER_HOURS', 30),
];
