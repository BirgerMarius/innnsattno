<?php

return [
    'retention_days' => env('EXTERNAL_DATA_EVENT_RETENTION_DAYS', 90),
    // Age of the last successful source refresh, not the cache insertion time.
    'max_age_minutes' => [
        'tv' => env('EXTERNAL_DATA_TV_MAX_AGE_MINUTES', 360),
        'tv_optional' => env('EXTERNAL_DATA_TV_MAX_AGE_MINUTES', 360),
        'football' => env('EXTERNAL_DATA_FOOTBALL_MAX_AGE_MINUTES', 360),
        'weather' => env('EXTERNAL_DATA_WEATHER_MAX_AGE_MINUTES', 180),
        'prayer' => env('EXTERNAL_DATA_PRAYER_MAX_AGE_MINUTES', 10080),
        'news' => env('EXTERNAL_DATA_NEWS_MAX_AGE_MINUTES', 1440),
        'calendar' => env('EXTERNAL_DATA_CALENDAR_MAX_AGE_MINUTES', 10080),
    ],
    'failure_count' => env('EXTERNAL_DATA_FAILURE_COUNT', 5),
    'failure_minutes' => env('EXTERNAL_DATA_FAILURE_MINUTES', 60),
    'missing_count' => env('EXTERNAL_DATA_MISSING_COUNT', 3),
    'missing_minutes' => env('EXTERNAL_DATA_MISSING_MINUTES', 10),
    'max_check_gap_minutes' => env('EXTERNAL_DATA_MAX_CHECK_GAP_MINUTES', 60),
    'reminder_minutes' => env('EXTERNAL_DATA_REMINDER_MINUTES', 1440),
    'tv_batch_size' => env('EXTERNAL_DATA_TV_BATCH_SIZE', 8),
];
