<?php

/*
 * Aktiveres bare når ordlyd og kilde er kontrollert mot offisiell informasjon.
 */
return [
    'enabled' => true,

    'from' => '2026-08-28',
    // Keep the official notice active until the funeral date is confirmed.
    'until' => null,

    'title' => 'H.M. Kong Harald V er død.',
    'message' => 'Norge er i en nasjonal sørgeperiode. Det flagges på halv stang fra statlige bygninger frem til bisettelsesdagen.',

    'source_url' => 'https://www.kongehuset.no/',
    'source_name' => 'Kongehuset.no',

];
