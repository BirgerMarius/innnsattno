<?php

return [
    /*
     * Set once when the recommendation is published, using Oslo local time.
     * The card is shown for four weeks from this time.
     * Example: 2026-10-08 09:00:00
     */
    'published_at' => env('PODCAST_RECOMMENDATION_PUBLISHED_AT'),
];
