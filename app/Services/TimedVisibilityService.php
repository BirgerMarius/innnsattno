<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Throwable;

class TimedVisibilityService
{
    public const TIMEZONE = 'Europe/Oslo';

    /** @return array{starts_at: CarbonImmutable, ends_at: CarbonImmutable}|null */
    public function window(mixed $startsAt, int $durationInDays): ?array
    {
        if (! is_string($startsAt) || trim($startsAt) === '' || $durationInDays < 1) {
            return null;
        }

        try {
            $start = CarbonImmutable::parse($startsAt, self::TIMEZONE);

            return ['starts_at' => $start, 'ends_at' => $start->addDays($durationInDays)];
        } catch (Throwable) {
            return null;
        }
    }

    public function isVisible(mixed $startsAt, int $durationInDays, ?CarbonInterface $now = null): bool
    {
        $window = $this->window($startsAt, $durationInDays);
        $currentTime = $now
            ? CarbonImmutable::instance($now)->setTimezone(self::TIMEZONE)
            : CarbonImmutable::now(self::TIMEZONE);

        return $window !== null
            && $currentTime->greaterThanOrEqualTo($window['starts_at'])
            && $currentTime->lessThan($window['ends_at']);
    }
}
