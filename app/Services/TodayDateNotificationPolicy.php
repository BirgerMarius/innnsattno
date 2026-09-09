<?php

namespace App\Services;

use Carbon\CarbonInterface;

/** Keeps crawler requests for arbitrary calendar dates out of administrator mail. */
class TodayDateNotificationPolicy
{
    public function shouldNotify(CarbonInterface $date): bool
    {
        $today = now('Europe/Oslo')->startOfDay();
        $requested = $date->copy()->setTimezone('Europe/Oslo')->startOfDay();
        $window = max(0, (int) config('services.today.notification_window_days', 31));

        return $requested->betweenIncluded($today->copy()->subDays($window), $today->copy()->addDays($window));
    }
}
