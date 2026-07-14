<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'football_api' => [
        'key' => env('FOOTBALL_API_KEY'),
        'base_url' => env('FOOTBALL_API_BASE_URL', 'https://v3.football.api-sports.io'),
        'season' => env('FOOTBALL_API_SEASON'),
        'cache_ttl' => env('FOOTBALL_API_CACHE_TTL', 900),
        'leagues' => [
            'premier_league' => env('FOOTBALL_API_PREMIER_LEAGUE_ID', 39),
        ],
    ],

    'schibsted_sports' => [
        'base_url' => env('SCHIBSTED_SPORTS_BASE_URL', 'https://api.sportsnext.schibsted.io/v1/vg'),
        'cache_ttl' => env('SCHIBSTED_SPORTS_CACHE_TTL', 900),
        'timeout' => env('SCHIBSTED_SPORTS_TIMEOUT', 10),
        'retry_times' => env('SCHIBSTED_SPORTS_RETRY_TIMES', 1),
        'retry_sleep' => env('SCHIBSTED_SPORTS_RETRY_SLEEP', 250),
        'premier_league_tournament_id' => env('SCHIBSTED_PREMIER_LEAGUE_TOURNAMENT_ID', 3),
        'premier_league_season_id' => env('SCHIBSTED_PREMIER_LEAGUE_SEASON_ID', 9186),
        'catalog_path' => env('SCHIBSTED_FOOTBALL_CATALOG_PATH', storage_path('app/reference/schibsted-football-tournaments.json')),
        'known_season_ids' => [
            7767,
            9186,
        ],
    ],

];
