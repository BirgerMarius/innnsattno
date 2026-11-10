<?php

/*
 * Example only: this file is not loaded by Laravel. Copy the entry into
 * config/prisons.php -> all after verifying every local value.
 * Required: unique array key (URL-ID), name and all feature switches.
 * Disabled services need no related values and must not borrow another
 * prison's data, channels or special functions.
 */
return [
    'example-fengsel' => [
        'name' => 'Eksempel fengsel', // Required display name.
        'features' => [ // Required booleans.
            'weather' => false,
            'local_news' => false,
            'tv' => false,
            'prayer' => false,
            'visitation' => false,
        ],
        // Required only when weather is true. Preserve the WEATHER_<...>
        // environment-variable pattern if the coordinate must be overrideable.
        'weather' => [
            'name' => 'Verifisert værsted',
            'latitude' => env('WEATHER_EXAMPLE_FENGSEL_LATITUDE'),
            'longitude' => env('WEATHER_EXAMPLE_FENGSEL_LONGITUDE'),
        ],
        // Required only when tv is true. Use only verified VG kanal-ID-er;
        // an empty list does not enable a TV guide.
        'tv_channels' => ['verified-channel-slug'],
        // Required only when prayer is true; do not invent an ID.
        'prayer_location_id' => null,
        // Optional. Required fields when local_news is true: source_name,
        // sources[].url (HTTPS), and sources[].article_hosts.
        'local_news' => [
            'source_name' => 'Verifisert lokal avis',
            'sources' => [[
                'url' => 'https://example.invalid/emne',
                'article_hosts' => ['example.invalid', 'www.example.invalid'],
            ]],
        ],
        // Optional official link. Omit it when no verified URL exists.
        'official_url' => 'https://www.kriminalomsorgen.no/',
        // Required only when visitation is true. Use verified cells only.
        'visitation_departments' => ['A' => ['A-101']],
    ],
];
