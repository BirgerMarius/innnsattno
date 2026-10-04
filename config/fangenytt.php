<?php

return [
    /*
     * Set this once when Fangenytt is published, using Oslo local time.
     * Do not change it on later deploys: the Nyhet badge is shown for 14 days.
     * Example: 2026-10-03 10:00:00
     */
    'published_at' => env('FANGENYTT_PUBLISHED_AT'),

    // Utgavene er lagt inn manuelt etter kontroll hos Fangeforeningen.
    // original_url er kilde/referanse; local_file er arkivfilen som vises fra Innsatt.no.
    // Nye utgaver kan legges øverst i denne listen.
    'issues' => [
        ['number' => 18, 'original_url' => 'https://www.fangeforeningen.no/wp-content/uploads/2026/10/Fangenytt-magasin-18-pdf.pdf', 'local_file' => 'fangenytt-18.pdf'],
        ['number' => 17, 'original_url' => 'https://www.fangeforeningen.no/wp-content/uploads/2026/06/Fangenytt-magasin-17-5.pdf', 'local_file' => 'fangenytt-17.pdf'],
        ['number' => 16, 'edition' => '2/2026', 'original_url' => 'https://www.fangeforeningen.no/wp-content/uploads/2026/05/Fangenytt-nr.-16.pdf', 'local_file' => 'fangenytt-16.pdf'],
        ['number' => 15, 'edition' => '1/2026', 'original_url' => 'https://www.fangeforeningen.no/wp-content/uploads/2026/02/Fangenytt-magasin-15.pdf', 'local_file' => 'fangenytt-15.pdf'],
        ['number' => 14, 'edition' => '8/2025', 'original_url' => 'https://www.fangeforeningen.no/wp-content/uploads/2026/01/Fangenytt-magasin-14.pdf', 'local_file' => 'fangenytt-14.pdf'],
        ['number' => 13, 'edition' => '7/2025', 'original_url' => 'https://www.fangeforeningen.no/wp-content/uploads/2026/02/Fangenytt-magasin-13.pdf', 'local_file' => 'fangenytt-13.pdf'],
        ['number' => 12, 'edition' => '6/2025', 'original_url' => 'https://www.fangeforeningen.no/wp-content/uploads/2026/02/Fangenytt-magasin-12-oppdatert.pdf', 'local_file' => 'fangenytt-12.pdf'],
        ['number' => 11, 'edition' => '5/2025', 'original_url' => 'https://www.fangeforeningen.no/wp-content/uploads/2026/02/NYHETSBREV-nr-11-ferdig.pdf', 'local_file' => 'fangenytt-11.pdf'],
        ['number' => 10, 'original_url' => 'https://www.fangeforeningen.no/wp-content/uploads/2025/05/NYHETSBREV-nr-10.pdf', 'local_file' => 'fangenytt-10.pdf'],
        ['number' => 9, 'original_url' => 'https://www.fangeforeningen.no/wp-content/uploads/2025/04/NYHETSBREV-nr-9.pdf', 'local_file' => 'fangenytt-9.pdf'],
        ['number' => 8, 'original_url' => 'https://www.fangeforeningen.no/wp-content/uploads/Nyhetsbrev/NYHETSBREV-nr-8.pdf', 'local_file' => 'fangenytt-8.pdf'],
        ['number' => 7, 'edition' => '1/2025', 'original_url' => 'https://www.fangeforeningen.no/wp-content/uploads/Nyhetsbrev/NYHETSBREV-nr-7.pdf', 'local_file' => 'fangenytt-7.pdf'],
        ['number' => 6, 'edition' => '6/2024', 'original_url' => 'https://www.fangeforeningen.no/wp-content/uploads/Nyhetsbrev/NYHETSBREV-nr-6.pdf', 'local_file' => 'fangenytt-6.pdf'],
        ['number' => 5, 'original_url' => 'https://www.fangeforeningen.no/wp-content/uploads/Nyhetsbrev/NYHETSBREV-nr-5.pdf', 'local_file' => 'fangenytt-5.pdf'],
        ['number' => 4, 'original_url' => 'https://www.fangeforeningen.no/wp-content/uploads/Nyhetsbrev/ff-nyhetsbrev-4.pdf', 'local_file' => 'fangenytt-4.pdf'],
        ['number' => 3, 'original_url' => 'https://www.fangeforeningen.no/wp-content/uploads/Nyhetsbrev/ff-nyhetsbrev-3.pdf', 'local_file' => 'fangenytt-3.pdf'],
        ['number' => 2, 'original_url' => 'https://www.fangeforeningen.no/wp-content/uploads/Nyhetsbrev/ff-nyhetsbrev-2.pdf', 'local_file' => 'fangenytt-2.pdf'],
        ['number' => 1, 'edition' => '1/2024', 'original_url' => 'https://www.fangeforeningen.no/wp-content/uploads/Nyhetsbrev/ff-nyhetsbrev.pdf', 'local_file' => 'fangenytt-1.pdf'],
    ],
];
