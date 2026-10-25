<?php

/*
 * The prison is always selected by the URL.  Keep all local differences here;
 * pages and services must not grow prison-specific copies.
 */
return [
    'default' => 'ringerike',
    'all' => [
        'ringerike' => [
            'name' => 'Ringerike fengsel',
            'weather_key' => 'ringerike',
            'prayer_location_id' => 146,
            'tv_available' => true,
            'official_url' => 'https://www.kriminalomsorgen.no/ringerike-fengsel.5031519-237612.html',
            'local_news' => [
                'source_name' => 'Ringerikes Blad',
                'source_url' => 'https://www.ringblad.no/ringerike-fengsel/',
                'article_hosts' => ['ringblad.no', 'www.ringblad.no'],
            ],
        ],
        'ilseng' => [
            'name' => 'Ilseng fengsel',
            'weather_key' => 'ilseng',
            'prayer_location_id' => 181,
            'tv_available' => true,
            'official_url' => 'https://www.kriminalomsorgen.no/fengsel/innlandet-kriminalomsorgen-innlandet-avd-lavere-sikkerhet-ilseng',
            'local_news' => [
                'source_name' => 'Hamar Arbeiderblad',
                'source_url' => 'https://www.h-a.no/ilseng-fengsel/',
                'article_hosts' => ['h-a.no', 'www.h-a.no'],
            ],
        ],
        'kongsvinger' => [
            'name' => 'Kongsvinger fengsel',
            'weather_key' => 'kongsvinger',
            // Bønnetid.no has no publicly readable location catalogue. Set the
            // verified provider ID in production before enabling this page.
            'prayer_location_id' => env('BONNETID_KONGSVINGER_LOCATION_ID'),
            'tv_available' => false,
            'official_url' => 'https://www.kriminalomsorgen.no/fengsel/innlandet-kriminalomsorgen-innlandet-avd-utenlandske-innsatte-kongsvinger',
            'local_news' => [
                'source_name' => 'Glåmdalen',
                'source_url' => 'https://www.glomdalen.no/kongsvinger-fengsel/',
                'article_hosts' => ['glomdalen.no', 'www.glomdalen.no'],
            ],
        ],
    ],
];
