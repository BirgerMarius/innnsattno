<?php

return [
    'repository_path' => env('CHANGE_HISTORY_REPOSITORY_PATH'),
    'cache_ttl' => (int) env('CHANGE_HISTORY_CACHE_TTL', 3600),
];
