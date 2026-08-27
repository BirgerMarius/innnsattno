<?php

/*
 * Aktiveres bare etter at dato/periode, ordlyd og source_url er kontrollert mot
 * den offisielle meldingen fra regjeringen eller UD om sørgeflagging.
 */
return [
    'enabled' => false,

    'from' => null,
    'until' => null,

    'title' => 'Offisiell sørgeflagging',
    'message' => 'Det er besluttet sørgeflagging ved offentlige bygninger. Det flagges på halv stang.',

    'source_url' => null,
    'source_name' => 'Regjeringen.no',

    'half_staff' => true,
];
