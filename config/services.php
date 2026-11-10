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

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'quiz_model' => env('OPENAI_QUIZ_MODEL', 'gpt-5.6-terra'),
        'timeout' => env('OPENAI_TIMEOUT', 60),
    ],

    'wikidata' => [
        'sparql_url' => env('WIKIDATA_SPARQL_URL', 'https://query.wikidata.org/sparql'),
        'user_agent' => env('WIKIDATA_USER_AGENT', 'innsatt.no/1.0 innsatt@innsatt.no'),
        'timeout' => env('WIKIDATA_TIMEOUT', 20),
        'cache_ttl' => env('WIKIDATA_QUIZ_CACHE_TTL', 86400),
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
        'eliteserien_tournament_id' => env('SCHIBSTED_ELITESERIEN_TOURNAMENT_ID', 38),
        'eliteserien_season_id' => env('SCHIBSTED_ELITESERIEN_SEASON_ID', 8766),
        'obos_ligaen_tournament_id' => env('SCHIBSTED_OBOS_LIGAEN_TOURNAMENT_ID', 40),
        'obos_ligaen_season_id' => env('SCHIBSTED_OBOS_LIGAEN_SEASON_ID', 8767),
        'la_liga_tournament_id' => env('SCHIBSTED_LA_LIGA_TOURNAMENT_ID', 2),
        'la_liga_season_id' => env('SCHIBSTED_LA_LIGA_SEASON_ID', 9227),
        'bundesliga_tournament_id' => env('SCHIBSTED_BUNDESLIGA_TOURNAMENT_ID', 6),
        'bundesliga_season_id' => env('SCHIBSTED_BUNDESLIGA_SEASON_ID', 9237),
        'champions_league_tournament_id' => env('SCHIBSTED_CHAMPIONS_LEAGUE_TOURNAMENT_ID', 1),
        'champions_league_season_id' => env('SCHIBSTED_CHAMPIONS_LEAGUE_SEASON_ID', 9168),
        'europa_league_tournament_id' => env('SCHIBSTED_EUROPA_LEAGUE_TOURNAMENT_ID', 14),
        'europa_league_season_id' => env('SCHIBSTED_EUROPA_LEAGUE_SEASON_ID', 9170),
        'conference_league_tournament_id' => env('SCHIBSTED_CONFERENCE_LEAGUE_TOURNAMENT_ID', 469),
        'conference_league_season_id' => env('SCHIBSTED_CONFERENCE_LEAGUE_SEASON_ID', 9171),
        'nations_league_tournament_id' => env('SCHIBSTED_NATIONS_LEAGUE_TOURNAMENT_ID', 187),
        'nations_league_season_id' => env('SCHIBSTED_NATIONS_LEAGUE_SEASON_ID', 8889),
        'catalog_path' => env('SCHIBSTED_FOOTBALL_CATALOG_PATH', storage_path('app/reference/schibsted-football-tournaments.json')),
        'known_season_ids' => [
            7767,
            9186,
            8766,
            8767,
            9227,
            9237,
            9168,
            9170,
            9171,
            8889,
        ],
    ],

    'weather' => [
        'base_url' => env('WEATHER_API_BASE_URL', 'https://api.met.no/weatherapi/locationforecast/2.0/compact'),
        'user_agent' => env('WEATHER_USER_AGENT', 'innsatt.no/1.0 innsatt@innsatt.no'),
        'cache_ttl' => env('WEATHER_CACHE_TTL', 1800),
        'timeout' => env('WEATHER_TIMEOUT', 10),
    ],

    'prayer_times' => [
        'base_url' => env('BONNETID_API_BASE_URL', 'https://api.bonnetid.no/prayertimes'),
        'api_token' => env('BONNETID_API_TOKEN'),
    ],

    'today' => [
        'namedays_url' => env('NAMEDAYS_API_URL', 'https://webapi.no/api/v1/namedays'),
        'wikimedia_url' => env('WIKIMEDIA_ON_THIS_DAY_URL', 'https://no.wikipedia.org/api/rest_v1/feed/onthisday/all'),
        'wikimedia_fallback_url' => env('WIKIMEDIA_ON_THIS_DAY_FALLBACK_URL', 'https://en.wikipedia.org/api/rest_v1/feed/onthisday/all'),
        'wikipedia_api_url' => env('NORWEGIAN_WIKIPEDIA_API_URL', 'https://no.wikipedia.org/w/api.php'),
        'wikidata_url' => env('WIKIDATA_API_URL', 'https://www.wikidata.org/w/api.php'),
        'user_agent' => env('TODAY_API_USER_AGENT', 'innsatt.no/1.0 innsatt@innsatt.no'),
        'cache_ttl' => env('TODAY_API_CACHE_TTL', 86400),
        'namedays_cache_ttl' => env('NAMEDAYS_API_CACHE_TTL', 86400),
        'namedays_failure_cache_ttl' => env('NAMEDAYS_API_FAILURE_CACHE_TTL', 86400),
        'failure_cache_ttl' => env('TODAY_API_FAILURE_CACHE_TTL', 86400),
        // Only dates close to today represent normal interactive use. Older and
        // distant future pages remain available, but their source failures log
        // without sending administrator mail.
        'notification_window_days' => env('TODAY_API_NOTIFICATION_WINDOW_DAYS', 31),
        'timeout' => env('TODAY_API_TIMEOUT', 5),
    ],

];
