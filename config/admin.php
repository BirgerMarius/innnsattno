<?php

return [
    'email' => env('ADMIN_EMAIL'),
    'password_hash' => env('ADMIN_PASSWORD_HASH'),
    'statistics_summary_path' => env(
        'ADMIN_STATISTICS_SUMMARY_PATH',
        storage_path('app/statistikk/admin-summary.json')
    ),
    'goaccess_report_url' => env('ADMIN_GOACCESS_REPORT_URL', '/statistikk/'),
];
