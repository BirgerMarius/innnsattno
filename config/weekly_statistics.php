<?php

return [
    // Deliberately opt-in: a deploy must not send historical or test mail.
    'enabled' => env('WEEKLY_STATISTICS_REPORT_ENABLED', false),
    'recipient' => env('WEEKLY_STATISTICS_REPORT_RECIPIENT', 'innsatt@innsatt.no'),
];
