<?php

return [
    'front_page_cache_seconds' => 300,
    'national_max_age_days' => 10,
    'union_max_age_days' => 30,
    'national_threshold' => 80,
    'national_significance_threshold' => 60,
    'cluster_window_hours' => 72,
    'sources' => [
        'nff' => [
            'name' => 'NFF-magasinet',
            'url' => 'https://www.frifagbevegelse.no/nff-magasinet/?lab_viewport=rss',
            'interval_minutes' => 30,
            'kind' => 'union',
        ],
        'ky' => [
            'name' => 'KY',
            'url' => 'https://kysiden.no/feed/',
            'interval_minutes' => 30,
            'kind' => 'union',
        ],
        'kdi' => [
            'name' => 'Kriminalomsorgsdirektoratet',
            'url' => 'https://kommunikasjon.ntb.no/rss/releases/latest?publisherId=17847130',
            'interval_minutes' => 15,
            'kind' => 'national',
        ],
        'sivilombudet' => [
            'name' => 'Sivilombudet',
            'url' => 'https://www.sivilombudet.no/feed/',
            'interval_minutes' => 30,
            'kind' => 'national',
        ],
    ],
];
