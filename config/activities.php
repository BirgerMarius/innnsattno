<?php

return [
    /*
     * Set this once when Spillforslag is published, using Oslo local time.
     * Do not change it on later deploys: the New badge is shown for 14 days.
     * Example: 2026-10-09 09:00:00
     */
    'published_at' => env('SPILLFORSLAG_PUBLISHED_AT'),
];
