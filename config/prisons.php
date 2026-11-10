<?php

/*
 * The prison is always selected by the URL.  Keep all local differences here;
 * pages and services must not grow prison-specific copies.
 */
return [
    'default' => 'ringerike',
    /*
     * Copy config/prisons.example.php when preparing a new prison.  A prison
     * is not active until it is added below and passes prisons:validate.
     */
    'all' => [
        'ringerike' => [
            'name' => 'Ringerike fengsel',
            'features' => [
                'weather' => true, 'local_news' => true, 'tv' => true,
                'prayer' => true, 'visitation' => true,
            ],
            'weather' => [
                'name' => 'Tyristrand / Ringerike fengsel',
                'latitude' => env('WEATHER_RINGERIKE_LATITUDE', 60.087),
                'longitude' => env('WEATHER_RINGERIKE_LONGITUDE', 10.099),
            ],
            'tv_channels' => [
                'nrk1', 'nrk2', 'nrk3', 'tv2-direkte', 'tv2-zebra', 'tvnorge', 'tv3', 'tv3-plus',
                'tv2-sport-1', 'tv2-sport-2', 'eurosport-norge', 'eurosport-1', 'c-more-hits',
                'tv2-livsstil', 'rex', 'fem', 'national-geographic', 'discovery-channel',
                'viasat-explore', 'investigation-discovery', 'bbc-world-news', 'al-jazeera-english',
                'nickelodeon', 'dr1', 'mtv',
            ],
            'prayer_location_id' => 146,
            'official_url' => 'https://www.kriminalomsorgen.no/ringerike-fengsel.5031519-237612.html',
            'visitation_departments' => [
                'A' => [
                    'A-101', 'A-102', 'A-103', 'A-104', 'A-105', 'A-106', 'A-107', 'A-108', 'A-109', 'A-110',
                    'A-111', 'A-112', 'A-113', 'A-114', 'A-115', 'A-116', 'A-201', 'A-202', 'A-203', 'A-204',
                    'A-205', 'A-206', 'A-207', 'A-301', 'A-302', 'A-303', 'A-304', 'A-305', 'A-306', 'A-307',
                ],
                'B' => [
                    'B-101', 'B-102', 'B-103', 'B-104', 'B-105', 'B-106', 'B-107', 'B-201', 'B-202', 'B-203',
                    'B-204', 'B-205', 'B-206', 'B-207', 'B-208', 'B-209', 'B-210', 'B-211', 'B-212', 'B-213',
                    'B-214', 'B-301', 'B-302', 'B-303', 'B-304', 'B-305', 'B-306', 'B-307', 'B-308', 'B-309',
                    'B-310', 'B-311', 'B-312', 'B-313', 'B-314', 'B-401', 'B-402', 'B-403', 'B-404', 'B-405',
                    'B-406', 'B-407', 'B-408', 'B-409', 'B-410', 'B-411', 'B-412', 'B-413', 'B-414',
                ],
                'C' => [
                    'C-101', 'C-102', 'C-103', 'C-104', 'C-105', 'C-106', 'C-107', 'C-108', 'C-109', 'C-110',
                    'C-111', 'C-112', 'C-113', 'C-114', 'C-201', 'C-202', 'C-203', 'C-204', 'C-205', 'C-206',
                    'C-207', 'C-301', 'C-302', 'C-303', 'C-304', 'C-305', 'C-306', 'C-307', 'C-308', 'C-309',
                    'C-310', 'C-311', 'C-312', 'C-313', 'C-314', 'C-401', 'C-402', 'C-403', 'C-404', 'C-405',
                    'C-406', 'C-407', 'C-408', 'C-409', 'C-410', 'C-411', 'C-412', 'C-413', 'C-414',
                ],
                'D' => [
                    'D-101', 'D-102', 'D-103', 'D-104', 'D-105', 'D-106', 'D-107', 'D-108', 'D-109', 'D-110',
                    'D-111', 'D-112', 'D-113', 'D-114', 'D-201', 'D-202', 'D-203', 'D-204', 'D-205', 'D-206',
                    'D-207', 'D-208', 'D-209', 'D-210', 'D-211', 'D-212', 'D-213', 'D-214',
                ],
            ],
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
            'features' => [
                'weather' => true, 'local_news' => true, 'tv' => true,
                'prayer' => true, 'visitation' => false,
            ],
            'weather' => [
                'name' => 'Ilseng fengsel',
                'latitude' => env('WEATHER_ILSENG_LATITUDE', 60.779295),
                'longitude' => env('WEATHER_ILSENG_LONGITUDE', 11.2281577),
            ],
            'tv_channels' => [
                'nrk1', 'nrk2', 'nrk3', 'tv2-direkte', 'tv2-zebra', 'tv2-livsstil', 'tv2-nyheter',
                'tvnorge', 'tv3', 'tv3-plus', 'tv6', 'fem', 'rex', 'vox', 'discovery-channel',
                'national-geographic', 'eurosport-1', 'eurosport-norge', 'tv2-sport-1', 'tv2-sport-2',
                'v-sport-1', 'v-sport-2', 'v-sport-3', 'v-film-premiere', 'v-film-action', 'v-series',
                'bbc-nordic', 'disney-channel', 'history', 'tlc',
            ],
            'prayer_location_id' => 181,
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
            'features' => [
                'weather' => true, 'local_news' => true, 'tv' => true,
                'prayer' => true, 'visitation' => true,
            ],
            'weather' => [
                'name' => 'Kongsvinger fengsel – Vardåsveien 190',
                'latitude' => env('WEATHER_KONGSVINGER_LATITUDE', 60.2102457),
                'longitude' => env('WEATHER_KONGSVINGER_LONGITUDE', 11.9851459),
            ],
            // Bekreftet mot VGs TV-API 02.11.2026. Eurosports norske
            // sendeskjema identifiserer fengselets «Eurosport 2» som
            // «Eurosport Norge» (kanalfamilie EUROSPORT2_NORWAY).
            // Ikke legg til forespurte 5HD før kanalidentiteten er verifisert.
            'tv_channels' => [
                'nrk1', 'nrk2', 'nrk3',
                'tv2-direkte', 'tv2-zebra', 'tv2-livsstil', 'tv2-nyheter',
                'tvnorge', 'bbc-nordic', 'viasat-explore', 'history',
                'discovery-channel', 'investigation-discovery', 'rex', 'mtv',
                'svt1', 'svt2', 'eurosport-1', 'eurosport-norge', 'al-jazeera-english',
            ],
            'prayer_location_id' => 153,
            'visitation_departments' => [
                'H' => [
                    201, 202, 203, 204, 205, 206, 207, 208, 209, 210,
                    211, 212, 213, 214, 215, 216, 217, 218, 219, 220,
                ],
                'A' => [101, 102, 103, 104, 105, 201, 202, 203, 204, 205],
                'B' => [
                    101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112,
                    201, 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212,
                ],
                'F' => [101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115],
            ],
            'official_url' => 'https://www.kriminalomsorgen.no/fengsel/innlandet-kriminalomsorgen-innlandet-avd-utenlandske-innsatte-kongsvinger',
            'local_news' => [
                'source_name' => 'Glåmdalen',
                'description' => 'Nyheter fra Glåmdalen om fengsel og Kriminalomsorgen Innlandet.',
                'sources' => [
                    [
                        'label' => 'Fengsel',
                        'more_label' => 'Flere saker hos Glåmdalen – emneknagg: fengsel',
                        'url' => 'https://www.glomdalen.no/tag/fengsel',
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
