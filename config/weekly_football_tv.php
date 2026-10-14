<?php

return [
    /*
     * Set once when Fotball på TV is published, using Oslo local time.
     * Do not change it on later deploys: the Nyhet badge is shown for 14 days.
     * Example: 2026-10-14 09:00:00
     */
    'published_at' => env('FOTBALL_PA_TV_PUBLISHED_AT'),
];
