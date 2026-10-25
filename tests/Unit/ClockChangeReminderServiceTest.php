<?php

namespace Tests\Unit;

use App\Services\ClockChangeReminderService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class ClockChangeReminderServiceTest extends TestCase
{
    /** @dataProvider reminderDates */
    public function testReminderUsesOsloCalendarDaysAndActualTransition(string $date, string $timezone, ?string $direction, bool $hasChanged = false): void
    {
        $reminder = (new ClockChangeReminderService)->forDate(CarbonImmutable::parse($date, $timezone));

        if ($direction === null) {
            $this->assertNull($reminder);
            return;
        }

        $this->assertSame($direction, $reminder['direction']);
        $this->assertSame($direction === 'frem' ? '02:00 → 03:00' : '03:00 → 02:00', $reminder['time']);
        $this->assertStringStartsWith($hasChanged ? 'I natt ble klokken stilt ' : 'I natt stilles klokken ', $reminder['message']);
    }

    public static function reminderDates(): array
    {
        return [
            'spring Friday' => ['2026-03-27 23:59:59', 'Europe/Oslo', null],
            'spring Saturday starts' => ['2026-03-28 00:00:00', 'Europe/Oslo', 'frem'],
            'spring Sunday before change' => ['2026-03-29 01:59:59', 'Europe/Oslo', 'frem'],
            'spring exact transition UTC' => ['2026-03-29 01:00:00', 'UTC', 'frem', true],
            'spring Sunday ends' => ['2026-03-29 23:59:59', 'Europe/Oslo', 'frem', true],
            'spring Monday' => ['2026-03-30 00:00:00', 'Europe/Oslo', null],
            'autumn Friday' => ['2026-10-23 23:59:59', 'Europe/Oslo', null],
            'autumn Saturday starts' => ['2026-10-24 00:00:00', 'Europe/Oslo', 'tilbake'],
            'autumn Sunday before change' => ['2026-10-25 00:59:59', 'UTC', 'tilbake'],
            'autumn exact transition' => ['2026-10-25 01:00:00', 'UTC', 'tilbake', true],
            'autumn Sunday before the special end' => ['2026-10-25 12:59:59', 'Europe/Oslo', 'tilbake', true],
            'autumn Sunday special end' => ['2026-10-25 13:00:00', 'Europe/Oslo', null],
            'autumn Monday' => ['2026-10-26 00:00:00', 'Europe/Oslo', null],
            'UTC Friday is Oslo Saturday' => ['2026-10-23 22:00:00', 'UTC', 'tilbake'],
            'future spring' => ['2027-03-27 12:00:00', 'Europe/Oslo', 'frem'],
            'future autumn' => ['2027-10-31 12:00:00', 'Europe/Oslo', 'tilbake', true],
            'leap year' => ['2028-03-25 12:00:00', 'Europe/Oslo', 'frem'],
            'ordinary day' => ['2026-07-10 12:00:00', 'Europe/Oslo', null],
            'new year' => ['2027-01-01 00:00:00', 'Europe/Oslo', null],
        ];
    }
}
