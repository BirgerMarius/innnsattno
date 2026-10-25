<?php

namespace App\Services;

use DateTimeInterface;

class HeaderMessageService
{
    public function __construct(
        private ClockChangeReminderService $clockChanges,
        private DailyWordService $dailyWords,
    ) {
    }

    /** @return array{type: string, title: string, message: string}|null */
    public function forDate(?DateTimeInterface $date = null): ?array
    {
        $clockReminder = $this->clockChanges->forDate($date);

        if ($clockReminder !== null) {
            return ['type' => 'clock-change'] + $clockReminder;
        }

        return $this->dailyWords->forDate($date);
    }

    public function nextChangeAt(?DateTimeInterface $date = null): ?\Carbon\CarbonImmutable
    {
        return $this->clockChanges->nextMessageChangeAt($date);
    }
}
