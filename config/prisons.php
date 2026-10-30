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
            'tv_channels' => [
                'nrk1', 'nrk2', 'nrk3', 'tv2-direkte', 'tv2-zebra', 'tvnorge', 'tv3', 'tv3-plus',
                'tv2-sport-1', 'tv2-sport-2', 'eurosport-norge', 'eurosport-1', 'c-more-hits',
                'tv2-livsstil', 'rex', 'fem', 'national-geographic', 'discovery-channel',
                'viasat-explore', 'investigation-discovery', 'bbc-world-news', 'al-jazeera-english',
                'nickelodeon', 'dr1', 'mtv',
            ],
            'weather_key' => 'ringerike',
            'prayer_location_id' => 146,
            'tv_available' => true,
            'visitation_available' => true,
            'official_url' => 'https://www.kriminalomsorgen.no/ringerike-fengsel.5031519-237612.html',
            'local_news' => [
                'source_name' => 'Ringerikes Blad',
                'sources' => [[
                    'url' => 'https://www.ringblad.no/ringerike-fengsel/',
                    'article_hosts' => ['ringblad.no', 'www.ringblad.no'],
                ]],
            ],
        ],
        'ilseng' => [
            'name' => 'Ilseng fengsel',
            'tv_channels' => [
                'nrk1', 'nrk2', 'nrk3', 'tv2-direkte', 'tv2-zebra', 'tv2-livsstil', 'tv2-nyheter',
                'tvnorge', 'tv3', 'tv3-plus', 'tv6', 'fem', 'rex', 'vox', 'discovery-channel',
                'national-geographic', 'eurosport-1', 'eurosport-norge', 'tv2-sport-1', 'tv2-sport-2',
                'v-sport-1', 'v-sport-2', 'v-sport-3', 'v-film-premiere', 'v-film-action', 'v-series',
                'bbc-nordic', 'disney-channel', 'history', 'tlc',
            ],
            'weather_key' => 'ilseng',
            'prayer_location_id' => 181,
            'tv_available' => true,
            'visitation_available' => false,
            'official_url' => 'https://www.kriminalomsorgen.no/fengsel/innlandet-kriminalomsorgen-innlandet-avd-lavere-sikkerhet-ilseng',
            'local_news' => [
                'source_name' => 'Hamar Arbeiderblad',
                'sources' => [[
                    'url' => 'https://www.h-a.no/ilseng-fengsel/',
                    'article_hosts' => ['h-a.no', 'www.h-a.no'],
                ]],
            ],
        ],
        'kongsvinger' => [
            'name' => 'Kongsvinger fengsel',
            // Kanalutvalg er ikke mottatt. En tom liste er bevisst og må aldri
            // erstattes med kanalene fra et annet fengsel.
            'tv_channels' => [],
            'weather_key' => 'kongsvinger',
            // Bønnetid.no has no publicly readable location catalogue. Set the
            // verified provider ID in production before enabling this page.
            'prayer_location_id' => env('BONNETID_KONGSVINGER_LOCATION_ID'),
            'tv_available' => false,
            'visitation_available' => false,
            'official_url' => 'https://www.kriminalomsorgen.no/fengsel/innlandet-kriminalomsorgen-innlandet-avd-utenlandske-innsatte-kongsvinger',
            'local_news' => [
                'source_name' => 'Glåmdalen',
                'description' => 'Nyheter fra Glåmdalen om Kongsvinger fengsel og Kriminalomsorgen Innlandet.',
                'sources' => [
                    [
                        'label' => 'Kongsvinger fengsel',
                        'more_label' => 'Flere saker hos Glåmdalen – emneknagg: Kongsvinger fengsel',
                        'url' => 'https://www.glomdalen.no/kongsvinger-fengsel/',
                        'article_hosts' => ['glomdalen.no', 'www.glomdalen.no'],
                    ],
                    [
                        'label' => 'Kriminalomsorgen Innlandet',
                        'more_label' => 'Flere saker hos Glåmdalen – emneknagg: Kriminalomsorgen Innlandet',
                        'url' => 'https://www.glomdalen.no/tag/kriminalomsorgen-innlandet',
                        'article_hosts' => ['glomdalen.no', 'www.glomdalen.no'],
                    ],
                ],
            ],
        ],
    ],
];
