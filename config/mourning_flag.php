<?php

/*
 * Aktiveres bare etter at dato/periode, ordlyd og source_url er kontrollert mot
 * den offisielle meldingen fra regjeringen eller UD om sørgeflagging.
 */
return [
    'enabled' => true,

    'from' => '2026-08-28',
    'until' => '2026-08-28',

    'title' => 'Offisiell sørgeflagging',
    'message' => 'H.M. Kong Harald V er død.',

    'source_url' => 'https://www.kongehuset.no/',
    'source_name' => 'Kongehuset.no',

    'half_staff' => true,
];
